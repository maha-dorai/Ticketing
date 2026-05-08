<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Requests\AssignMembersRequest;
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

            $project->users()->sync($request->user_ids);

            return response()->json(['message' => 'Membres affectés avec succès.']);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Erreur lors de l\'affectation.', 'error' => $e->getMessage()], 500);
        }
    }
}