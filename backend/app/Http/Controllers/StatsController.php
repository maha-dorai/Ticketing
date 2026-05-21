<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StatsController extends Controller
{
    public function getAdminDashboardStats()
    {
        if (!Auth::user()->isAdmin()) {
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
}
