<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\ChefDeProjet;
use App\Models\Admin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class ChefDeProjetController extends Controller
{
    /**
     * Créer un compte chef_de_projet.
     */
    public function create(Request $request)
    {
        try {
            $request->validate([
                'nom'    => 'required|string',
                'prenom' => 'required|string',
                'email'  => 'required|email|unique:users,email',
            ], [
                'email.unique' => 'Cette adresse email est déjà associée à un compte.',
            ]);

            $tempPassword = $this->generateSecurePassword();

            // 1. Créer le User de base
            $chef = User::create([
                'nom'                   => $request->nom,
                'prenom'                => $request->prenom,
                'email'                 => $request->email,
                'mot_de_passe'          => Hash::make($tempPassword),
                'force_password_change' => true,
            ]);

            // 2. Créer le ChefDeProjet associé
            ChefDeProjet::create([
                'user_id' => $chef->id,
            ]);

            try {
                $body = "Bonjour {$chef->prenom},\n\n"
                      . "Un compte Chef de projet a été créé pour vous sur la plateforme Ticketing.\n\n"
                      . "Vos identifiants de connexion :\n"
                      . "Email                   : {$chef->email}\n"
                      . "Mot de passe temporaire : {$tempPassword}\n\n"
                      . "⚠️  Vous serez obligé(e) de changer ce mot de passe lors de votre première connexion.\n\n"
                      . "— L'équipe Ticketing";

                Mail::raw($body, fn($m) => $m->to($chef->email)->subject('🔐 Votre compte Chef de projet — Ticketing'));
            } catch (\Exception $mailEx) {}

            return response()->json([
                'message'        => "Compte chef de projet créé. Les identifiants ont été envoyés à {$chef->email}.",
                'chef_de_projet' => [
                    'id'     => $chef->id,
                    'nom'    => $chef->nom,
                    'prenom' => $chef->prenom,
                    'email'  => $chef->email,
                ],
            ], 201);

        } catch (\Exception $e) {
            return response()->json(['message' => 'Erreur serveur.', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Liste tous les chefs de projet.
     */
    public function list()
    {
        try {
            $chefs = User::with(['chefDeProjet.admin'])
                ->whereHas('chefDeProjet')
                ->get()
                ->map(function ($user) {
                    return [
                        'id'                    => $user->id,
                        'nom'                   => $user->nom,
                        'prenom'                => $user->prenom,
                        'email'                 => $user->email,
                        'statut'                => 'actif',
                        'force_password_change' => $user->force_password_change,
                        'created_at'            => $user->created_at,
                    ];
                });

            return response()->json($chefs);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Erreur serveur.', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Révoquer un chef de projet (supprimer son enregistrement ChefDeProjet).
     */
    public function revoke($id)
    {
        try {
            $user = User::with('chefDeProjet')->findOrFail($id);

            if (!$user->chefDeProjet) {
                return response()->json(['message' => 'Cet utilisateur n\'est pas un chef de projet.'], 400);
            }

            // Supprimer le record ChefDeProjet (et Admin en cascade)
            $user->chefDeProjet->delete();

            return response()->json(['message' => 'Compte chef de projet désactivé.']);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Erreur serveur.', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Génère un mot de passe sécurisé.
     */
    private function generateSecurePassword(): string
    {
        $upper   = strtoupper(Str::random(2));
        $lower   = strtolower(Str::random(6));
        $digits  = rand(10, 99);
        $specials = ['!', '@', '#', '$', '%', '^', '&', '*'];
        $special  = $specials[array_rand($specials)];

        return str_shuffle($upper . $lower . $digits . $special);
    }
}