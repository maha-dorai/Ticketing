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
            $user = JWTAuth::parseToken()->authenticate();
            $query = $user->role === 'admin' ? Project::query() : $user->projects();

            if ($request->has('search') && !empty($request->search)) {
                $query->where('nom', 'like', '%' . $request->search . '%');
            }

            $projects = $query->with('users:id,nom,prenom,role')->paginate(10);

            return response()->json($projects);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Erreur serveur.', 'error' => $e->getMessage()], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'nom'         => 'required|string|max:255',
                'description' => 'nullable|string',
                'date_debut'  => 'nullable|date',
                'date_fin'    => 'nullable|date|after_or_equal:date_debut',
            ]);

            // ✅ CORRIGÉ : statut initial = 'ouvert' selon CDC §4.1
            $validated['statut'] = 'ouvert';

            $project = Project::create($validated);

            return response()->json(['message' => 'Projet créé avec succès.', 'project' => $project], 201);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Erreur lors de la création.', 'error' => $e->getMessage()], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $project = Project::findOrFail($id);

            $validated = $request->validate([
                'nom'    => 'required|string|max:255',
                // ✅ CORRIGÉ : termine supprimé, remplacé par ouvert/en_cours/ferme (CDC §4.1)
                'statut' => 'required|in:ouvert,en_cours,ferme',
            ]);

            $project->update($validated);

            return response()->json(['message' => 'Projet mis à jour.']);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Erreur lors de la mise à jour.', 'error' => $e->getMessage()], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $project = Project::findOrFail($id);
            // CDC §4.3 : "La suppression d'un projet est interdite, il peut seulement être fermé"
            $project->update(['statut' => 'ferme']);

            return response()->json(['message' => 'Projet fermé avec succès.']);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Erreur lors de la fermeture.', 'error' => $e->getMessage()], 500);
        }
    }

    public function assignUsers(AssignMembersRequest $request, $id)
    {
        try {
            $project = Project::findOrFail($id);
            $project->users()->sync($request->validated()['user_ids']);

            return response()->json(['message' => 'Membres affectés avec succès.']);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Erreur lors de l\'affectation.', 'error' => $e->getMessage()], 500);
        }
    }
}