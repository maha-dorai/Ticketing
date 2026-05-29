<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\User;
use App\Models\Ticket;
use App\Mail\ProjectAssigned;
use Illuminate\Http\Request;
use App\Http\Requests\AssignMembersRequest;
use Illuminate\Support\Facades\Mail;
use Tymon\JWTAuth\Facades\JWTAuth;

class ProjectController extends Controller
{
    public function index(Request $request)
    {
        try {
            $user  = JWTAuth::parseToken()->authenticate();
            
            // Logique de visibilité :
            // Admin -> Tous les projets
            // Chef de projet -> Ses propres projets créés
            // Membres -> Projets auxquels ils sont assignés
            if ($user->isAdmin()) {
                $query = Project::query();
            } elseif ($user->role === 'chef_de_projet') {
                $query = Project::where('created_by', $user->id);
            } else {
                $query = $user->projects();
            }

            if ($request->filled('search')) {
                $query->where('nom', 'like', '%' . $request->search . '%');
            }

            // Pour les filtres éventuels de statut
            if ($request->filled('statut')) {
                $query->where('statut', $request->statut);
            }

            $projects = $query->with([
                'users:id,nom,prenom,role',
                'creator:id,nom,prenom',
            ])->withCount('tickets')->orderBy('created_at', 'desc')->paginate(50); // Mieux pour le Kanban

            return response()->json($projects);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Erreur serveur.', 'error' => $e->getMessage()], 500);
        }
    }

    public function show($id)
    {
        try {
            $user    = JWTAuth::parseToken()->authenticate();
            $project = Project::with([
                'users:id,nom,prenom,role,email',
                'creator:id,nom,prenom',
            ])->findOrFail($id);

            // Admin: tout voir. Chef: seulement les siens. Membres: seulement assignés
            if ($user->isAdmin()) {
                // ok
            } elseif ($user->role === 'chef_de_projet') {
                if ($project->created_by !== $user->id) {
                    return response()->json(['message' => 'Accès non autorisé'], 403);
                }
            } else {
                $isMember = $project->users->contains('id', $user->id);
                if (!$isMember) {
                    return response()->json(['message' => 'Accès non autorisé'], 403);
                }
            }

            // Ajouter le workload de chaque membre manuellement
            $project->users->each(function ($member) {
                $member->active_tickets_count = \App\Models\Ticket::where('developpeur_id', $member->id)
                    ->where('assignment_status', 'approved')
                    ->whereIn('etat', ['OUVERT', 'EN_COURS'])
                    ->count();
            });

            return response()->json($project, 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Projet introuvable'], 404);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Erreur serveur.', 'error' => $e->getMessage()], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $creator = JWTAuth::parseToken()->authenticate();

            if (!$creator->isManager()) {
                return response()->json(['message' => 'Non autorisé'], 403);
            }

            $request->validate([
                'nom'         => 'required|string|max:255|unique:projects,nom',
                'description' => 'nullable|string',
                'date_debut'  => 'nullable|date',
                'date_fin'    => 'nullable|date|after_or_equal:date_debut',
                'user_ids'    => 'required|array|min:1',
                'user_ids.*'  => 'exists:users,id',
            ], [
                'nom.required'    => 'Le nom du projet est obligatoire.',
                'nom.unique'      => 'Un projet avec ce nom existe déjà.',
                'user_ids.required' => 'Vous devez assigner au moins un membre au projet.',
                'user_ids.min'    => 'Vous devez assigner au moins un membre au projet.',
            ]);

            // Vérifier que tous les membres sont actifs
            $inactiveUsers = User::whereIn('id', $request->user_ids)
                                 ->where('statut', '!=', 'actif')
                                 ->pluck('email');

            if ($inactiveUsers->isNotEmpty()) {
                return response()->json([
                    'message'         => 'Certains membres ne sont pas actifs et ne peuvent pas être affectés.',
                    'comptes_bloqués' => $inactiveUsers,
                ], 422);
            }

            $project = Project::create([
                'nom'         => $request->nom,
                'description' => $request->description,
                'date_debut'  => $request->date_debut,
                'date_fin'    => $request->date_fin,
                'statut'      => 'ouvert',
                'created_by'  => $creator->id,
            ]);

            // Assigner les membres et envoyer les emails
            $project->users()->sync($request->user_ids);

            $newUsers = User::whereIn('id', $request->user_ids)->get();
            foreach ($newUsers as $u) {
                \App\Http\Controllers\NotificationController::createAndBroadcast(
                    $u->id,
                    "📁 Vous avez été ajouté(e) au projet « {$project->nom} ».",
                    null
                );
                try {
                    Mail::to($u->email)->send(new ProjectAssigned($project, $u));
                } catch (\Exception $e) {}
            }

            return response()->json(['message' => 'Projet créé avec succès.', 'project' => $project], 201);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Erreur lors de la création.', 'error' => $e->getMessage()], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();
            $project = Project::findOrFail($id);

            // Seul le créateur peut modifier
            if ($project->created_by !== $user->id) {
                return response()->json(['message' => "Non autorisé. Seul le créateur du projet peut le modifier."], 403);
            }

            $request->validate([
                'nom'    => 'required|string|max:255',
                'statut' => 'required|in:ouvert,en_cours,archive',
            ], [
                'nom.required'    => 'Le nom du projet est obligatoire.',
                'statut.in'       => 'Le statut doit être : ouvert, en_cours ou archive.',
            ]);

            // Empêcher de revenir à 'ouvert' si le projet est déjà commencé
            if ($request->statut === 'ouvert' && in_array($project->statut, ['en_cours', 'archive'])) {
                return response()->json(['message' => "Un projet déjà en cours ne peut pas repasser à l'état Ouvert."], 422);
            }

            // Bloquer le passage manuel ouvert → en_cours (automatique via premier ticket)
            if ($request->statut === 'en_cours' && $project->statut === 'ouvert') {
                return response()->json(['message' => "Le projet passe en cours automatiquement lors de la création du premier ticket."], 422);
            }

            // Vérification avant de fermer
            if ($request->statut === 'archive' && $project->statut !== 'archive') {
                $nonValideTickets = $project->tickets()->where('etat', '!=', 'VALIDE')->count();
                if ($nonValideTickets > 0) {
                    return response()->json(['message' => "Impossible de fermer le projet. $nonValideTickets ticket(s) ne sont pas validés."], 422);
                }
            }

            $project->update($request->only('nom', 'statut', 'description', 'date_debut', 'date_fin'));

            // Notifier les membres du projet du changement
            $members = $project->users()->pluck('users.id')->toArray();
            foreach ($members as $memberId) {
                \App\Http\Controllers\NotificationController::createAndBroadcast(
                    $memberId,
                    "📁 Le projet « {$project->nom} » a été mis à jour.",
                    null
                );
            }

            return response()->json(['message' => 'Projet mis à jour.', 'project' => $project]);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Erreur lors de la mise à jour.', 'error' => $e->getMessage()], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();
            $project = Project::findOrFail($id);

            if ($project->created_by !== $user->id) {
                return response()->json(['message' => "Non autorisé. Seul le créateur du projet peut le fermer."], 403);
            }

            $nonValideTickets = $project->tickets()->where('etat', '!=', 'VALIDE')->count();
            if ($nonValideTickets > 0) {
                return response()->json(['message' => "Impossible de fermer le projet. $nonValideTickets ticket(s) ne sont pas validés."], 422);
            }

            $project->update(['statut' => 'archive']);

            return response()->json(['message' => 'Projet fermé (archivé) avec succès.']);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Erreur lors de la fermeture.', 'error' => $e->getMessage()], 500);
        }
    }

    public function assignUsers(Request $request, $id)
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();
            $project = Project::findOrFail($id);

            if ($project->created_by !== $user->id) {
                return response()->json(['message' => "Non autorisé. Seul le créateur du projet peut assigner des membres."], 403);
            }

            $request->validate([
                'user_ids'   => 'required|array',
                'user_ids.*' => 'exists:users,id',
            ]);

            $inactiveUsers = User::whereIn('id', $request->user_ids)
                                 ->where('statut', '!=', 'actif')
                                 ->pluck('email');

            if ($inactiveUsers->isNotEmpty()) {
                return response()->json([
                    'message'        => 'Certains membres ne sont pas actifs et ne peuvent pas être affectés.',
                    'comptes_bloqués' => $inactiveUsers,
                ], 422);
            }

            $oldUserIds = $project->users()->pluck('users.id')->toArray();
            $newUserIds = array_diff($request->user_ids, $oldUserIds);

            $project->users()->sync($request->user_ids);

            $newUsers = User::whereIn('id', $newUserIds)->get();

            foreach ($newUsers as $u) {
                \App\Http\Controllers\NotificationController::createAndBroadcast(
                    $u->id,
                    "📁 Vous avez été ajouté(e) au projet « {$project->nom} ».",
                    null
                );
                try {
                    Mail::to($u->email)->send(new ProjectAssigned($project, $u));
                } catch (\Exception $e) {}
            }

            return response()->json(['message' => 'Membres affectés avec succès.']);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Erreur lors de l\'affectation.', 'error' => $e->getMessage()], 500);
        }
    }

    public function getDevelopersWorkload($projectId)
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();
            
            if (!$user->isManager()) {
                return response()->json(['message' => 'Non autorisé'], 403);
            }

            $project = Project::findOrFail($projectId);
            
            $developers = $project->users()->where('role', 'developpeur')->get()->map(function ($dev) {
                $activeCount = \App\Models\Ticket::where('developpeur_id', $dev->id)
                    ->where('assignment_status', 'approved')
                    ->whereIn('etat', ['OUVERT', 'EN_COURS'])
                    ->count();
                
                return [
                    'id' => $dev->id,
                    'nom' => $dev->nom,
                    'prenom' => $dev->prenom,
                    'statut' => $dev->statut,
                    'active_tickets_count' => $activeCount,
                ];
            });

            return response()->json($developers, 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Erreur serveur.', 'error' => $e->getMessage()], 500);
        }
    }
}