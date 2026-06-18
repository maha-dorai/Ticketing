<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StatsController extends Controller
{
    public function getAdminDashboardStats()
    {
        if (!Auth::user()->isManager()) {
            return response()->json(['message' => 'Non autorisé'], 403);
        }

        $users = User::withCount([
            'projects',
            'createdTickets as active_created_tickets' => function ($query) {
                $query->whereIn('etat', ['OUVERT', 'EN_COURS']);
            },
            'createdTickets as closed_created_tickets' => function ($query) {
                $query->whereIn('etat', ['RESOLU', 'FERME']);
            },
            'assignedTickets as active_assigned_tickets' => function ($query) {
                $query->where('assignment_status', 'approved')
                      ->whereIn('etat', ['OUVERT', 'EN_COURS']);
            },
            'assignedTickets as closed_assigned_tickets' => function ($query) {
                $query->where('assignment_status', 'approved')
                      ->whereIn('etat', ['RESOLU', 'FERME']);
            }
        ])->get();

        $stats = $users->map(function ($user) {
            $active = $user->role === 'testeur' ? $user->active_created_tickets : $user->active_assigned_tickets;
            $closed = $user->role === 'testeur' ? $user->closed_created_tickets : $user->closed_assigned_tickets;
            
            return [
                'id' => $user->id,
                'nom' => $user->nom,
                'prenom' => $user->prenom,
                'email' => $user->email,
                'role' => $user->role,
                'statut' => $user->statut,
                'projects_count' => $user->projects_count,
                'active_tickets_count' => $active,
                'closed_tickets_count' => $closed,
            ];
        });

        return response()->json($stats, 200);
    }

    public function getUserStats()
    {
        $user = Auth::user();

        $userData = User::withCount([
            'projects',
            'createdTickets as active_created_tickets' => function ($query) {
                $query->whereIn('etat', ['OUVERT', 'EN_COURS']);
            },
            'createdTickets as closed_created_tickets' => function ($query) {
                $query->whereIn('etat', ['RESOLU', 'FERME']);
            },
            'assignedTickets as active_assigned_tickets' => function ($query) {
                $query->where('assignment_status', 'approved')
                      ->whereIn('etat', ['OUVERT', 'EN_COURS']);
            },
            'assignedTickets as closed_assigned_tickets' => function ($query) {
                $query->where('assignment_status', 'approved')
                      ->whereIn('etat', ['RESOLU', 'FERME']);
            }
        ])->find($user->id);

        $active = $user->role === 'testeur' ? $userData->active_created_tickets : $userData->active_assigned_tickets;
        $closed = $user->role === 'testeur' ? $userData->closed_created_tickets : $userData->closed_assigned_tickets;

        return response()->json([
            'projects_count' => $userData->projects_count,
            'active_tickets_count' => $active,
            'closed_tickets_count' => $closed,
        ], 200);
    }

    private function getDateRange(Request $request)
    {
        $period = $request->query('period', 'month');
        $now = \Carbon\Carbon::now();
        
        switch ($period) {
            case 'today':
                return [$now->copy()->startOfDay(), $now->copy()->endOfDay()];
            case 'week':
                return [$now->copy()->subDays(7)->startOfDay(), $now->copy()->endOfDay()];
            case 'month':
                return [$now->copy()->subDays(30)->startOfDay(), $now->copy()->endOfDay()];
            case 'all':
            default:
                return [\Carbon\Carbon::create(2000, 1, 1), $now->copy()->endOfDay()];
        }
    }

    public function admin(Request $request)
    {
        $user = Auth::user();
        if ($user->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        [$startDate, $endDate] = $this->getDateRange($request);
        $query = \App\Models\Ticket::whereBetween('created_at', [$startDate, $endDate]);

        // 1. Tickets par statut (Donut)
        $ticketsByStatus = (clone $query)->select('etat', \Illuminate\Support\Facades\DB::raw('count(*) as count'))
                                        ->groupBy('etat')->get();

        // 2. Activité globale (Courbe : créés vs résolus par jour)
        $activityCreated = (clone $query)
            ->select(\Illuminate\Support\Facades\DB::raw('DATE(created_at) as date'), \Illuminate\Support\Facades\DB::raw('count(*) as count'))
            ->groupBy('date')->orderBy('date')->get();
        
        $activityResolved = \App\Models\Ticket::whereBetween('updated_at', [$startDate, $endDate])
            ->whereIn('etat', ['VALIDE', 'RESOLU', 'FERME'])
            ->select(\Illuminate\Support\Facades\DB::raw('DATE(updated_at) as date'), \Illuminate\Support\Facades\DB::raw('count(*) as count'))
            ->groupBy('date')->orderBy('date')->get();

        // 3. Tickets par projet (Barres empilées)
        $ticketsByProject = (clone $query)->select('project_id', 'etat', \Illuminate\Support\Facades\DB::raw('count(*) as count'))
            ->with('project:id,nom')->groupBy('project_id', 'etat')->get();

        // 4. Chefs de projet actifs (KPI)
        $totalChefs = User::where('role', 'chef_de_projet')->count();
        $activeChefs = User::where('role', 'chef_de_projet')->where('statut', 'actif')->count();

        // 5. Membres par rôle (Camembert)
        $membersByRole = User::whereIn('role', ['testeur', 'developpeur'])
                             ->select('role', \Illuminate\Support\Facades\DB::raw('count(*) as count'))
                             ->groupBy('role')->get();

        // 6. Total projets (KPI)
        $totalProjects = \App\Models\Project::count();

        // 7. Évolution des projets (Créés vs Archivés) - Courbe
        $projectActivityCreated = \App\Models\Project::whereBetween('created_at', [$startDate, $endDate])
            ->select(\Illuminate\Support\Facades\DB::raw('DATE(created_at) as date'), \Illuminate\Support\Facades\DB::raw('count(*) as count'))
            ->groupBy('date')->orderBy('date')->get();

        $projectActivityArchived = \App\Models\Project::whereBetween('updated_at', [$startDate, $endDate])
            ->where('statut', 'archive')
            ->select(\Illuminate\Support\Facades\DB::raw('DATE(updated_at) as date'), \Illuminate\Support\Facades\DB::raw('count(*) as count'))
            ->groupBy('date')->orderBy('date')->get();

        // 8. Taux d'avancement par projet (Barres ou Radar)
        $projectsAdvancement = \App\Models\Project::withCount([
            'tickets as total_tickets',
            'tickets as resolved_tickets' => function ($query) {
                $query->whereIn('etat', ['VALIDE', 'RESOLU', 'FERME']);
            }
        ])->get()->map(function($p) {
            $rate = $p->total_tickets > 0 ? round(($p->resolved_tickets / $p->total_tickets) * 100) : 0;
            return ['nom' => $p->nom, 'rate' => $rate];
        });

        return response()->json([
            'tickets_by_status' => $ticketsByStatus,
            'activity_created' => $activityCreated,
            'activity_resolved' => $activityResolved,
            'tickets_by_project' => $ticketsByProject,
            'kpi_chefs' => ['active' => $activeChefs, 'total' => $totalChefs],
            'members_by_role' => $membersByRole,
            'total_projects' => $totalProjects,
            'project_activity_created' => $projectActivityCreated,
            'project_activity_archived' => $projectActivityArchived,
            'projects_advancement' => $projectsAdvancement,
        ]);
    }

    public function manager(Request $request, $projectId = null)
    {
        $user = Auth::user();
        if (!in_array($user->role, ['chef_de_projet', 'admin'])) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        [$startDate, $endDate] = $this->getDateRange($request);
        $query = \App\Models\Ticket::whereBetween('created_at', [$startDate, $endDate]);
        
        if ($projectId) {
            $query->where('project_id', $projectId);
        }

        // 1. Avancement projet (Donut)
        $totalTickets = (clone $query)->count();
        $resolvedTickets = (clone $query)->whereIn('etat', ['VALIDE', 'RESOLU', 'FERME'])->count();

        // 2. Tickets par priorité (Barres)
        $ticketsByPriority = (clone $query)->select('priorite', \Illuminate\Support\Facades\DB::raw('count(*) as count'))
                                           ->groupBy('priorite')->get();

        // 3. Charge par membre (Barres horiz)
        $chargeByMember = (clone $query)->whereNotNull('developpeur_id')
            ->select('developpeur_id', 'etat', \Illuminate\Support\Facades\DB::raw('count(*) as count'))
            ->with('developpeur:id,prenom,nom')->groupBy('developpeur_id', 'etat')->get();

        // 4. Évolution tickets (Courbe)
        $activityCreated = (clone $query)
            ->select(\Illuminate\Support\Facades\DB::raw('DATE(created_at) as date'), \Illuminate\Support\Facades\DB::raw('count(*) as count'))
            ->groupBy('date')->orderBy('date')->get();
        
        $resolvedQuery = \App\Models\Ticket::whereBetween('updated_at', [$startDate, $endDate])
            ->whereIn('etat', ['VALIDE', 'RESOLU', 'FERME']);
        if ($projectId) {
            $resolvedQuery->where('project_id', $projectId);
        }
        $activityResolved = (clone $resolvedQuery)
            ->select(\Illuminate\Support\Facades\DB::raw('DATE(updated_at) as date'), \Illuminate\Support\Facades\DB::raw('count(*) as count'))
            ->groupBy('date')->orderBy('date')->get();

        // 5. Tickets par type/catégorie (Camembert)
        $ticketsByCategory = (clone $query)->whereNotNull('categorie_ia')
            ->select('categorie_ia', \Illuminate\Support\Facades\DB::raw('count(*) as count'))
            ->groupBy('categorie_ia')->get();

        // 6. Délai moyen résolution (KPI) en heures
        if (\Illuminate\Support\Facades\DB::getDriverName() === 'sqlite') {
            $avgResolutionTime = (clone $resolvedQuery)->select(\Illuminate\Support\Facades\DB::raw('AVG((strftime("%s", updated_at) - strftime("%s", created_at)) / 3600) as avg_hours'))->first()->avg_hours;
        } else {
            $avgResolutionTime = (clone $resolvedQuery)->select(\Illuminate\Support\Facades\DB::raw('AVG(TIMESTAMPDIFF(HOUR, created_at, updated_at)) as avg_hours'))->first()->avg_hours;
        }

        // 7. Heatmap d'activité (Jour x Heure)
        if (\Illuminate\Support\Facades\DB::getDriverName() === 'sqlite') {
            $heatmap = (clone $query)->select(
                \Illuminate\Support\Facades\DB::raw("CASE strftime('%w', created_at)
                    WHEN '0' THEN 'Sunday'
                    WHEN '1' THEN 'Monday'
                    WHEN '2' THEN 'Tuesday'
                    WHEN '3' THEN 'Wednesday'
                    WHEN '4' THEN 'Thursday'
                    WHEN '5' THEN 'Friday'
                    WHEN '6' THEN 'Saturday'
                END as day"),
                \Illuminate\Support\Facades\DB::raw("CAST(strftime('%H', created_at) as integer) as hour"),
                \Illuminate\Support\Facades\DB::raw("count(*) as count")
            )->groupBy('day', 'hour')->get();
        } else {
            $heatmap = (clone $query)->select(
                \Illuminate\Support\Facades\DB::raw('DAYNAME(created_at) as day'),
                \Illuminate\Support\Facades\DB::raw('HOUR(created_at) as hour'),
                \Illuminate\Support\Facades\DB::raw('count(*) as count')
            )->groupBy('day', 'hour')->get();
        }

        return response()->json([
            'avancement' => ['total' => $totalTickets, 'resolus' => $resolvedTickets],
            'tickets_by_priority' => $ticketsByPriority,
            'charge_by_member' => $chargeByMember,
            'activity_created' => $activityCreated,
            'activity_resolved' => $activityResolved,
            'tickets_by_category' => $ticketsByCategory,
            'avg_resolution_hours' => round((float)$avgResolutionTime, 1),
            'heatmap' => $heatmap,
        ]);
    }

    public function me(Request $request)
    {
        $user = Auth::user();
        [$startDate, $endDate] = $this->getDateRange($request);
        
        // 1. Mes tickets ouverts (KPI)
        $myTicketsQuery = \App\Models\Ticket::where(function($q) use ($user) {
            $q->where('developpeur_id', $user->id)->orWhere('testeur_id', $user->id);
        })->whereBetween('created_at', [$startDate, $endDate]);

        $totalMine = (clone $myTicketsQuery)->count();
        $myTicketsByStatus = (clone $myTicketsQuery)->select('etat', \Illuminate\Support\Facades\DB::raw('count(*) as count'))
                                                    ->groupBy('etat')->get();

        // 2. Mon activité (Courbe)
        $myActivityCreated = (clone $myTicketsQuery)
            ->select(\Illuminate\Support\Facades\DB::raw('DATE(created_at) as date'), \Illuminate\Support\Facades\DB::raw('count(*) as count'))
            ->groupBy('date')->orderBy('date')->get();

        // 3. Répartition de ma charge (Donut % par projet)
        $myTicketsByProject = (clone $myTicketsQuery)->select('project_id', \Illuminate\Support\Facades\DB::raw('count(*) as count'))
            ->with('project:id,nom')->groupBy('project_id')->get();

        return response()->json([
            'my_kpi' => ['total' => $totalMine, 'by_status' => $myTicketsByStatus],
            'my_activity' => $myActivityCreated,
            'my_tickets_by_project' => $myTicketsByProject,
        ]);
    }
}
