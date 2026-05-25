<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tymon\JWTAuth\Facades\JWTAuth;

class UserController extends Controller
{
    // ─── PROFIL ───────────────────────────────────────────────────────────────

    public function getProfile()
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();
            return response()->json([
                'id'          => $user->id,
                'nom'         => $user->nom,
                'prenom'      => $user->prenom,
                'email'       => $user->email,
                'role'        => $user->role,
                'github_link' => $user->github_link,
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Erreur serveur.', 'error' => $e->getMessage()], 500);
        }
    }

    public function updateProfile(Request $request)
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();
            $request->validate(['nom' => 'required|string', 'prenom' => 'required|string']);
            $user->update(['nom' => $request->nom, 'prenom' => $request->prenom]);
            return response()->json(['message' => 'Profil mis à jour.']);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Erreur serveur.', 'error' => $e->getMessage()], 500);
        }
    }

    public function changePassword(Request $request)
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();
            $request->validate([
                'ancien_mot_de_passe'  => 'required',
                'nouveau_mot_de_passe' => [
                    'required', 'min:8',
                    'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).+$/'
                ],
            ], [
                'ancien_mot_de_passe.required'  => "L'ancien mot de passe est obligatoire.",
                'nouveau_mot_de_passe.required' => 'Le nouveau mot de passe est obligatoire.',
                'nouveau_mot_de_passe.min'      => 'Le nouveau mot de passe doit contenir au moins 8 caractères.',
                'nouveau_mot_de_passe.regex'    => 'Le mot de passe doit contenir au moins une majuscule, une minuscule, un chiffre et un caractère spécial.',
            ]);

            if (!Hash::check($request->ancien_mot_de_passe, $user->mot_de_passe))
                return response()->json(['message' => 'Le mot de passe actuel est incorrect.'], 400);

            $user->update([
                'mot_de_passe'          => Hash::make($request->nouveau_mot_de_passe),
                'force_password_change' => false, // Réinitialise le flag après le changement obligatoire
            ]);
            return response()->json(['message' => 'Mot de passe modifié avec succès.']);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['message' => collect($e->errors())->flatten()->first()], 422);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Erreur serveur.', 'error' => $e->getMessage()], 500);
        }
    }

    public function changeEmail(Request $request)
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();
            $request->validate([
                'new_email'    => 'required|email|unique:users,email',
                'mot_de_passe' => 'required',
            ], [
                'new_email.unique' => 'Cette adresse email est déjà associée à un compte.',
            ]);

            if (!Hash::check($request->mot_de_passe, $user->mot_de_passe))
                return response()->json(['message' => 'Le mot de passe actuel est incorrect.'], 400);

            $user->update(['email' => $request->new_email]);
            return response()->json(['message' => 'Adresse email modifiée avec succès.']);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['message' => collect($e->errors())->flatten()->first()], 422);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Erreur serveur.', 'error' => $e->getMessage()], 500);
        }
    }

    // ─── ADMIN ────────────────────────────────────────────────────────────────

    public function getUser($id)
    {
        try {
            $user = User::select('id', 'nom', 'prenom', 'email', 'role', 'statut', 'github_link', 'created_at')
                        ->findOrFail($id);
            return response()->json($user);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Utilisateur introuvable.'], 404);
        }
    }

    public function getAllUsers()
    {
        try {
            $users = User::select('id', 'nom', 'prenom', 'email', 'role', 'statut', 'github_link', 'created_at')->get();
            return response()->json($users);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Erreur serveur.', 'error' => $e->getMessage()], 500);
        }
    }

    public function validateUser(Request $request, $id)
    {
        try {
            $request->validate(['action' => 'required|in:accepter,rejeter']);
            $user = User::findOrFail($id);

            if ($request->action === 'accepter') {
                $user->update(['statut' => 'actif']);
                $msg       = 'Compte activé.';
                $emailBody = "Bonjour {$user->prenom},\n\nVotre demande d'accès a été acceptée.\nBienvenue !\n— L'équipe Ticketing";
                $emailSubj = "✅ Votre compte a été activé";
            } else {
                $user->update(['statut' => 'rejete']);
                $msg       = 'Compte rejeté.';
                $emailBody = "Bonjour {$user->prenom},\n\nVotre demande d'accès a été refusée.\n— L'équipe Ticketing";
                $emailSubj = "❌ Demande d'accès refusée";
            }

            try {
                Mail::raw($emailBody, fn($m) => $m->to($user->email)->subject($emailSubj));
            } catch (\Exception $mEx) {
                // Ignore email failure
            }

            return response()->json(['message' => $msg]);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Erreur serveur.', 'error' => $e->getMessage()], 500);
        }
    }

    public function deactivateUser(Request $request, $id)
    {
        try {
            $requester = auth()->user();
            $user      = User::findOrFail($id);

            // Un utilisateur ne peut pas désactiver son propre compte
            if ($id == $requester->id)
                return response()->json(['message' => 'Vous ne pouvez pas désactiver votre propre compte.'], 403);

            // L'admin ne peut jamais être désactivé
            if ($user->role === 'admin')
                return response()->json(['message' => 'Impossible de désactiver un administrateur.'], 403);

            // Seul l'admin peut désactiver un chef_de_projet
            if ($user->role === 'chef_de_projet' && $requester->role !== 'admin')
                return response()->json(['message' => 'Seul l\'administrateur peut désactiver un chef de projet.'], 403);

            $user->update(['statut' => 'desactive']);
            return response()->json(['message' => 'Utilisateur désactivé avec succès.']);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Erreur serveur.', 'error' => $e->getMessage()], 500);
        }
    }

    public function reactivateUser($id)
    {
        try {
            $user = User::findOrFail($id);

            if ($user->statut !== 'desactive')
                return response()->json(['message' => 'Ce compte n\'est pas désactivé.'], 400);

            $user->update(['statut' => 'actif']);
            return response()->json(['message' => 'Compte réactivé avec succès.']);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Erreur serveur.', 'error' => $e->getMessage()], 500);
        }
    }

    public function updateUser(Request $request, $id)
    {
        try {
            $requester = auth()->user();
            $user      = User::findOrFail($id);

            // Seul un admin peut modifier un autre admin ou promouvoir en admin
            if (
                ($user->role === 'admin' || $request->role === 'admin')
                && $requester->role !== 'admin'
            ) {
                return response()->json([
                    'message' => 'Seul un administrateur peut modifier le rôle admin.',
                ], 403);
            }

            $validated = $request->validate([
                'nom'    => 'required|string',
                'prenom' => 'required|string',
                'email'  => 'required|email|unique:users,email,' . $id,
                'role'   => 'required|in:testeur,developpeur,chef_de_projet,admin',
            ]);
            $user->update($validated);
            return response()->json(['message' => 'Utilisateur mis à jour.']);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Erreur serveur.', 'error' => $e->getMessage()], 500);
        }
    }

    // ⚠️ Suppression définitive INTERDITE — traçabilité obligatoire (spec Sprint 1)
    public function deleteUser($id)
    {
        return response()->json([
            'message' => 'La suppression définitive d\'un compte est interdite. Utilisez la désactivation pour préserver la traçabilité.',
        ], 403);
    }
}