<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\User;
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
            $query = $user->isAdmin() ? Project::query() : $user->projects();

            if ($request->filled('search')) {
                $query->where('nom', 'like', '%' . $request->search . '%');
            }

            $projects = $query->with([
                'users:id,nom,prenom,role',
                'creator:id,nom,prenom',
            ])->paginate(10);

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
                'users' => function($q) {
                    $q->select('users.id', 'users.nom', 'users.prenom', 'users.role', 'users.email')
                      ->withCount([
                          'assignedTickets as active_tickets_count' => function ($query) {
                              $query->where('assignment_status', 'approved')
                                    ->whereIn('etat', ['OUVERT', 'EN_COURS']);
                          }
                      ]);
                },
                'creator:id,nom,prenom'
            ])->findOrFail($id);

            // Vérifier que l'utilisateur est membre ou admin
            if (!$user->isAdmin()) {
                $isMember = $project->users->contains('id', $user->id);
                if (!$isMember) {
                    return response()->json(['message' => 'Accès non autorisé'], 403);
                }
            }

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

            $request->validate([
                'nom'         => 'required|string|max:255|unique:projects,nom',   // ✅ Fix US07-E2 : nom unique
                'description' => 'nullable|string',
                'date_debut'  => 'nullable|date',
                'date_fin'    => 'nullable|date|after_or_equal:date_debut',
            ], [
                'nom.required' => 'Le nom du projet est obligatoire.',
                'nom.unique'   => 'Un projet avec ce nom existe déjà.',
            ]);

            $project = Project::create([
                'nom'         => $request->nom,
                'description' => $request->description,
                'date_debut'  => $request->date_debut,
                'date_fin'    => $request->date_fin,
                'statut'      => 'ouvert',
                'created_by'  => $creator->id,              // ✅ Fix Sprint 2 : created_by
            ]);

            return response()->json(['message' => 'Projet créé avec succès.', 'project' => $project], 201);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Erreur lors de la création.', 'error' => $e->getMessage()], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $project = Project::findOrFail($id);

            $request->validate([
                'nom'    => 'required|string|max:255',
                'statut' => 'required|in:ouvert,en_cours,archive',    // ✅ Fix prof : archive au lieu de ferme
            ], [
                'nom.required'    => 'Le nom du projet est obligatoire.',
                'statut.in'       => 'Le statut doit être : ouvert, en_cours ou archive.',
            ]);

            $project->update($request->only('nom', 'statut', 'description', 'date_debut', 'date_fin'));

            return response()->json(['message' => 'Projet mis à jour.']);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Erreur lors de la mise à jour.', 'error' => $e->getMessage()], 500);
        }
    }

    // Archiver un projet (interdit de supprimer — traçabilité)
    public function destroy($id)
    {
        try {
            $project = Project::findOrFail($id);
            $project->update(['statut' => 'archive']);        // ✅ Fix prof : archive au lieu de ferme

            return response()->json(['message' => 'Projet archivé avec succès.']);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Erreur lors de l\'archivage.', 'error' => $e->getMessage()], 500);
        }
    }

    public function assignUsers(Request $request, $id)
    {
        try {
            $project = Project::findOrFail($id);

            $request->validate([
                'user_ids'   => 'required|array',
                'user_ids.*' => 'exists:users,id',
            ]);

            // ✅ Fix US09-E1 : vérifier que tous les membres ont le statut 'actif'
            $inactiveUsers = User::whereIn('id', $request->user_ids)
                                 ->where('statut', '!=', 'actif')
                                 ->pluck('email');

            if ($inactiveUsers->isNotEmpty()) {
                return response()->json([
                    'message'        => 'Certains membres ne sont pas actifs et ne peuvent pas être affectés.',
                    'comptes_bloqués' => $inactiveUsers,
                ], 422);
            }




            // ✅ Fix : récupérer les anciens membres AVANT le sync
            $oldUserIds = $project->users()->pluck('users.id')->toArray();
            $newUserIds = array_diff($request->user_ids, $oldUserIds);

            // ✅ Fix principal : sauvegarder les membres en base de données
            $project->users()->sync($request->user_ids);

            // 🔔 Notification in-app + 📧 Email uniquement pour les NOUVEAUX membres
            $newUsers = User::whereIn('id', $newUserIds)->get();

            foreach ($newUsers as $user) {
                // In-app notification
                \App\Http\Controllers\NotificationController::createAndBroadcast(
                    $user->id,
                    "📁 Vous avez été ajouté(e) au projet « {$project->nom} ».",
                    null
                );
                // Email
                try {
                    Mail::to($user->email)->send(new ProjectAssigned($project, $user));
                } catch (\Exception $e) { /* ne pas bloquer si mail échoue */ }
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
            
            if (!$user->isAdmin()) {
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