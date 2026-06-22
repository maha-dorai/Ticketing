<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class StatsController extends Controller
{
    public function getAdminDashboardStats()
    {
        if (!Auth::user()->isManager()) {
            return response()->json(['message' => 'Non autorisé'], 403);
        }

        $users = User::with(['membre', 'chefDeProjet.admin'])
            ->withCount([
                'projects',
                'createdTickets as active_created_tickets' => function ($query) {
                    $query->whereIn('etat', ['OUVERT', 'EN_COURS']);
                },
                'createdTickets as closed_created_tickets' => function ($query) {
                    $query->whereIn('etat', ['VALIDE', 'FERME']);
                },
                'assignedTickets as active_assigned_tickets' => function ($query) {
                    $query->where('assignment_status', 'approved')
                          ->whereIn('etat', ['OUVERT', 'EN_COURS']);
                },
                'assignedTickets as closed_assigned_tickets' => function ($query) {
                    $query->where('assignment_status', 'approved')
                          ->whereIn('etat', ['VALIDE', 'FERME']);
                }
            ])->get();

        $stats = $users->map(function ($user) {
            $role   = $user->role;
            $active = $role === 'testeur' ? $user->active_created_tickets : $user->active_assigned_tickets;
            $closed = $role === 'testeur' ? $user->closed_created_tickets : $user->closed_assigned_tickets;

            return [
                'id'                  => $user->id,
                'nom'                 => $user->nom,
                'prenom'              => $user->prenom,
                'email'               => $user->email,
                'role'                => $role,
                'statut'              => $user->statut,
                'projects_count'      => $user->projects_count,
                'active_tickets_count' => $active,
                'closed_tickets_count' => $closed,
            ];
        });

        return response()->json($stats, 200);
    }

    public function getUserStats()
    {
        $user = Auth::user();
        $user->load(['membre', 'chefDeProjet.admin']);

        $userData = User::withCount([
            'projects',
            'createdTickets as active_created_tickets' => function ($query) {
                $query->whereIn('etat', ['OUVERT', 'EN_COURS']);
            },
            'createdTickets as closed_created_tickets' => function ($query) {
                $query->whereIn('etat', ['VALIDE', 'FERME']);
            },
            'assignedTickets as active_assigned_tickets' => function ($query) {
                $query->where('assignment_status', 'approved')
                      ->whereIn('etat', ['OUVERT', 'EN_COURS']);
            },
            'assignedTickets as closed_assigned_tickets' => function ($query) {
                $query->where('assignment_status', 'approved')
                      ->whereIn('etat', ['VALIDE', 'FERME']);
            }
        ])->find($user->id);

        $role   = $user->role;
        $active = $role === 'testeur' ? $userData->active_created_tickets : $userData->active_assigned_tickets;
        $closed = $role === 'testeur' ? $userData->closed_created_tickets : $userData->closed_assigned_tickets;

        return response()->json([
            'projects_count'      => $userData->projects_count,
            'active_tickets_count' => $active,
            'closed_tickets_count' => $closed,
        ], 200);
    }

    private function getDateRange(Request $request)
    {
        $period = $request->query('period', 'month');
        $now    = \Carbon\Carbon::now();

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
        $user->load(['chefDeProjet.admin']);

        if ($user->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        [$startDate, $endDate] = $this->getDateRange($request);
        $query = \App\Models\Ticket::whereBetween('created_at', [$startDate, $endDate]);

        // 1. Tickets par statut
        $ticketsByStatus = (clone $query)
            ->select('etat', DB::raw('count(*) as count'))
            ->groupBy('etat')->get();

        // 2. Activité globale
        $activityCreated = (clone $query)
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('count(*) as count'))
            ->groupBy('date')->orderBy('date')->get();

        $activityResolved = \App\Models\Ticket::whereBetween('updated_at', [$startDate, $endDate])
            ->whereIn('etat', ['VALIDE'])
            ->select(DB::raw('DATE(updated_at) as date'), DB::raw('count(*) as count'))
            ->groupBy('date')->orderBy('date')->get();

        // 3. Tickets par projet
        $ticketsByProject = (clone $query)
            ->select('project_id', 'etat', DB::raw('count(*) as count'))
            ->with('project:id,nom')
            ->groupBy('project_id', 'etat')->get();

        // 4. Chefs de projet actifs
        $totalChefs  = User::whereHas('chefDeProjet')->count();
        $activeChefs = $totalChefs; // chefs sont toujours actifs

        // 5. Membres par rôle
        $membersByRole = \App\Models\Membre::select('role', DB::raw('count(*) as count'))
            ->groupBy('role')->get();

        // 6. Total projets
        $totalProjects = \App\Models\Project::count();

        // 7. Évolution des projets
        $projectActivityCreated = \App\Models\Project::whereBetween('created_at', [$startDate, $endDate])
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('count(*) as count'))
            ->groupBy('date')->orderBy('date')->get();

        $projectActivityArchived = \App\Models\Project::whereBetween('updated_at', [$startDate, $endDate])
            ->where('statut', 'ferme')
            ->select(DB::raw('DATE(updated_at) as date'), DB::raw('count(*) as count'))
            ->groupBy('date')->orderBy('date')->get();

        // 8. Taux d'avancement par projet
        $projectsAdvancement = \App\Models\Project::withCount([
            'tickets as total_tickets',
            'tickets as resolved_tickets' => function ($query) {
                $query->whereIn('etat', ['VALIDE']);
            }
        ])->get()->map(function ($p) {
            $rate = $p->total_tickets > 0 ? round(($p->resolved_tickets / $p->total_tickets) * 100) : 0;
            return ['nom' => $p->nom, 'rate' => $rate];
        });

        return response()->json([
            'tickets_by_status'          => $ticketsByStatus,
            'activity_created'           => $activityCreated,
            'activity_resolved'          => $activityResolved,
            'tickets_by_project'         => $ticketsByProject,
            'kpi_chefs'                  => ['active' => $activeChefs, 'total' => $totalChefs],
            'members_by_role'            => $membersByRole,
            'total_projects'             => $totalProjects,
            'project_activity_created'   => $projectActivityCreated,
            'project_activity_archived'  => $projectActivityArchived,
            'projects_advancement'       => $projectsAdvancement,
        ]);
    }

    public function manager(Request $request, $projectId = null)
    {
        $user = Auth::user();
        $user->load(['membre', 'chefDeProjet.admin']);

        if (!in_array($user->role, ['chef_de_projet', 'admin'])) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        [$startDate, $endDate] = $this->getDateRange($request);
        $query = \App\Models\Ticket::whereBetween('created_at', [$startDate, $endDate]);

        if ($projectId) {
            $query->where('project_id', $projectId);
        }

        // 1. Avancement projet
        $totalTickets    = (clone $query)->count();
        $resolvedTickets = (clone $query)->whereIn('etat', ['VALIDE'])->count();

        // 2. Tickets par priorité
        $ticketsByPriority = (clone $query)
            ->select('priorite', DB::raw('count(*) as count'))
            ->groupBy('priorite')->get();

        // 3. Charge par membre
        $chargeByMember = (clone $query)->whereNotNull('developpeur_id')
            ->select('developpeur_id', 'etat', DB::raw('count(*) as count'))
            ->with('developpeur:id,prenom,nom')
            ->groupBy('developpeur_id', 'etat')->get();

        // 4. Évolution tickets
        $activityCreated = (clone $query)
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('count(*) as count'))
            ->groupBy('date')->orderBy('date')->get();

        $resolvedQuery = \App\Models\Ticket::whereBetween('updated_at', [$startDate, $endDate])
            ->whereIn('etat', ['VALIDE']);
        if ($projectId) {
            $resolvedQuery->where('project_id', $projectId);
        }
        $activityResolved = (clone $resolvedQuery)
            ->select(DB::raw('DATE(updated_at) as date'), DB::raw('count(*) as count'))
            ->groupBy('date')->orderBy('date')->get();

        // 5. Tickets par catégorie IA
        $ticketsByCategory = (clone $query)->whereNotNull('categorie_ia')
            ->select('categorie_ia', DB::raw('count(*) as count'))
            ->groupBy('categorie_ia')->get();

        // 6. Délai moyen résolution
        if (DB::getDriverName() === 'mysql') {
            $avgResolutionTime = (clone $resolvedQuery)
                ->select(DB::raw('AVG(TIMESTAMPDIFF(HOUR, created_at, updated_at)) as avg_hours'))
                ->first()->avg_hours;
        } else {
            $avgResolutionTime = (clone $resolvedQuery)
                ->select(DB::raw('AVG((julianday(updated_at) - julianday(created_at)) * 24) as avg_hours'))
                ->first()->avg_hours;
        }

        // 7. Heatmap d'activité
        if (DB::getDriverName() === 'mysql') {
            $heatmap = (clone $query)->select(
                DB::raw('DAYNAME(created_at) as day'),
                DB::raw('HOUR(created_at) as hour'),
                DB::raw('count(*) as count')
            )->groupBy('day', 'hour')->get();
        } else {
            $rawHeatmap = (clone $query)->select(
                DB::raw("strftime('%w', created_at) as day_num"),
                DB::raw("CAST(strftime('%H', created_at) AS INTEGER) as hour"),
                DB::raw('count(*) as count')
            )->groupBy('day_num', 'hour')->get();
            
            $daysMap = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
            $heatmap = $rawHeatmap->map(function ($item) use ($daysMap) {
                return [
                    'day' => $daysMap[$item->day_num] ?? 'Unknown',
                    'hour' => $item->hour,
                    'count' => $item->count,
                ];
            });
        }

        return response()->json([
            'avancement'          => ['total' => $totalTickets, 'resolus' => $resolvedTickets],
            'tickets_by_priority' => $ticketsByPriority,
            'charge_by_member'    => $chargeByMember,
            'activity_created'    => $activityCreated,
            'activity_resolved'   => $activityResolved,
            'tickets_by_category' => $ticketsByCategory,
            'avg_resolution_hours' => round((float)$avgResolutionTime, 1),
            'heatmap'             => $heatmap,
        ]);
    }

    public function me(Request $request)
    {
        $user = Auth::user();
        $user->load(['membre', 'chefDeProjet.admin']);

        [$startDate, $endDate] = $this->getDateRange($request);

        $myTicketsQuery = \App\Models\Ticket::where(function ($q) use ($user) {
            $q->where('developpeur_id', $user->id)->orWhere('created_by', $user->id);
        })->whereBetween('created_at', [$startDate, $endDate]);

        $totalMine       = (clone $myTicketsQuery)->count();
        $myTicketsByStatus = (clone $myTicketsQuery)
            ->select('etat', DB::raw('count(*) as count'))
            ->groupBy('etat')->get();

        $myActivityCreated = (clone $myTicketsQuery)
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('count(*) as count'))
            ->groupBy('date')->orderBy('date')->get();

        $myTicketsByProject = (clone $myTicketsQuery)
            ->select('project_id', DB::raw('count(*) as count'))
            ->with('project:id,nom')
            ->groupBy('project_id')->get();

        return response()->json([
            'my_kpi'               => ['total' => $totalMine, 'by_status' => $myTicketsByStatus],
            'my_activity'          => $myActivityCreated,
            'my_tickets_by_project' => $myTicketsByProject,
        ]);
    }
}