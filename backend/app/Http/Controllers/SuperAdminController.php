<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class SuperAdminController extends Controller
{
    /**
     * Créer un compte admin.
     * Le super_admin saisit : nom, prénom, email.
     * Le système génère un mot de passe temporaire et l'envoie par email.
     * L'admin sera forcé de le changer à sa première connexion.
     */
    public function createAdmin(Request $request)
    {
        try {
            $request->validate([
                'nom'    => 'required|string',
                'prenom' => 'required|string',
                'email'  => 'required|email|unique:users,email',
            ], [
                'email.unique' => 'Cette adresse email est déjà associée à un compte.',
            ]);

            // Génère un mot de passe temporaire sécurisé (12 caractères)
            $tempPassword = $this->generateSecurePassword();

            $admin = User::create([
                'nom'                   => $request->nom,
                'prenom'                => $request->prenom,
                'email'                 => $request->email,
                'mot_de_passe'          => Hash::make($tempPassword),
                'role'                  => 'chef_de_projet',
                'statut'                => 'actif',             // Directement actif, pas besoin de validation
                'force_password_change' => true,                // Doit changer son mdp à la première connexion
            ]);

            // Envoi du mot de passe temporaire par email
            try {
                $body = "Bonjour {$admin->prenom},\n\n"
                      . "Un compte administrateur a été créé pour vous sur la plateforme Ticketing.\n\n"
                      . "Vos identifiants de connexion :\n"
                      . "Email    : {$admin->email}\n"
                      . "Mot de passe temporaire : {$tempPassword}\n\n"
                      . "⚠️  Vous serez obligé(e) de changer ce mot de passe lors de votre première connexion.\n\n"
                      . "— L'équipe Ticketing";

                Mail::raw($body, fn($m) => $m->to($admin->email)->subject('🔐 Votre compte administrateur Ticketing'));
            } catch (\Exception $mailEx) {
                // Ne bloque pas la création si l'email échoue
            }

            return response()->json([
                'message' => "Compte admin créé. Les identifiants ont été envoyés à {$admin->email}.",
                'chef_de_projet'   => [
                    'id'     => $admin->id,
                    'nom'    => $admin->nom,
                    'prenom' => $admin->prenom,
                    'email'  => $admin->email,
                ],
            ], 201);

        } catch (\Exception $e) {
            return response()->json(['message' => 'Erreur serveur.', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Liste tous les admins (pour que le super_admin les gère).
     */
    public function listAdmins()
    {
        try {
            $admins = User::where('role', 'chef_de_projet')
                          ->select('id', 'nom', 'prenom', 'email', 'statut', 'force_password_change', 'created_at')
                          ->get();

            return response()->json($admins);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Erreur serveur.', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Révoquer un admin (le passer en testeur ou le désactiver).
     */
    public function revokeAdmin($id)
    {
        try {
            $admin = User::where('id', $id)->where('role', 'chef_de_projet')->firstOrFail();
            $admin->update(['statut' => 'desactive']);

            return response()->json(['message' => 'Compte admin désactivé.']);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Erreur serveur.', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Génère un mot de passe aléatoire respectant les critères de sécurité.
     * Format : majuscule + minuscules + chiffres + caractère spécial
     */
    private function generateSecurePassword(): string
    {
        $upper   = strtoupper(Str::random(2));
        $lower   = strtolower(Str::random(6));
        $digits  = rand(10, 99);
        $specials = ['!', '@', '#', '$', '%', '^', '&', '*'];
        $special  = $specials[array_rand($specials)];

        // Mélange tous les éléments
        $password = str_shuffle($upper . $lower . $digits . $special);

        return $password;
    }
}