<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Tymon\JWTAuth\Facades\JWTAuth;
use Carbon\Carbon;

class AuthController extends Controller
{
    /**
     * [Sprint 1] Inscription d'un nouvel utilisateur.
     * Cette fonction vérifie les données, hash le mot de passe, et crée l'utilisateur avec un statut 'en_attente'.
     */
    public function register(Request $request)
    {
        try {
            // 1. Validation stricte des données envoyées par le Frontend
            $request->validate([
                'nom'          => 'required|string',
                'prenom'       => 'required|string',
                'email'        => 'required|email|unique:users,email', // Vérifie en BDD que l'email n'est pas déjà pris
                'mot_de_passe' => [
                    'required', 'min:8',
                    'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).+$/' // Force la complexité
                ],
                'role'        => 'required|in:testeur,developpeur', // Seuls ces deux rôles peuvent s'inscrire librement
                'github_link' => 'required_if:role,developpeur|required_if:role,testeur|nullable|url',
            ], [
                // Messages d'erreurs personnalisés
                'nom.required'            => 'Le nom est obligatoire.',
                'prenom.required'         => 'Le prénom est obligatoire.',
                'email.required'          => "L'adresse email est obligatoire.",
                'email.email'             => "L'adresse email n'est pas valide.",
                'email.unique'            => 'Cette adresse email est déjà associée à un compte.',
                'mot_de_passe.required'   => 'Le mot de passe est obligatoire.',
                'mot_de_passe.min'        => 'Le mot de passe doit contenir au moins 8 caractères.',
                'mot_de_passe.regex'      => 'Le mot de passe doit contenir au moins une majuscule, une minuscule, un chiffre et un caractère spécial.',
                'role.required'           => 'Le rôle est obligatoire.',
                'github_link.required_if' => 'Le lien GitHub est obligatoire.',
                'github_link.url'         => 'Le lien GitHub doit être une URL valide.',
            ]);

            // 2. Création de l'utilisateur dans la base MySQL (via Eloquent)
            User::create([
                'nom'          => $request->nom,
                'prenom'       => $request->prenom,
                'email'        => $request->email,
                'mot_de_passe' => Hash::make($request->mot_de_passe), // IMPORTANT: Hachage du mot de passe
                'role'         => $request->role,
                'statut'       => 'en_attente', // L'utilisateur ne peut pas se connecter tant qu'un admin n'a pas validé
                'github_link'  => in_array($request->role, ['developpeur', 'testeur']) ? $request->github_link : null,
            ]);

            // 3. Retourne le code HTTP 201 (Created)
            return response()->json(['message' => "Compte créé. En attente de validation."], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422); // 422: Unprocessable Entity (Erreur de validation)
        } catch (\Exception $e) {
            return response()->json(['message' => 'Erreur d\'inscription.', 'error' => $e->getMessage()], 500); // 500: Server Error
        }
    }

    /**
     * [Sprint 1] Connexion de l'utilisateur.
     * Cette fonction vérifie les identifiants, le statut du compte, et renvoie un token JWT si tout est OK.
     */
    public function login(Request $request)
    {
        try {
            $request->validate(['email' => 'required|email', 'mot_de_passe' => 'required']);

            // 1. Cherche l'utilisateur par son email
            $user = User::where('email', $request->email)->first();

            // 2. Vérifie si l'utilisateur existe ET si le mot de passe correspond au hash stocké
            if (!$user || !Hash::check($request->mot_de_passe, $user->mot_de_passe))
                return response()->json(['message' => 'Email ou mot de passe incorrect.'], 401); // 401: Unauthorized

            // 3. Vérification des statuts bloquants (Règles métier)
            if ($user->statut === 'en_attente')
                return response()->json(['message' => 'Votre compte est en attente de validation.'], 403); // 403: Forbidden
            if ($user->statut === 'rejete')
                return response()->json(['message' => "Compte rejeté, contactez l'administrateur."], 403);
            if ($user->statut === 'desactive')
                return response()->json(['message' => "Votre compte a été désactivé."], 403);

            // 4. Tout est bon : on génère le JSON Web Token (JWT)
            $token = JWTAuth::fromUser($user);

            // 5. On renvoie le token et les infos basiques au Frontend
            return response()->json([
                'token' => $token,
                'user'  => [
                    'id'                    => $user->id,
                    'nom'                   => $user->nom,
                    'prenom'                => $user->prenom,
                    'email'                 => $user->email,
                    'role'                  => $user->role,
                    'force_password_change' => (bool) $user->force_password_change, // Indique au frontend s'il doit afficher l'écran de changement de mdp
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Erreur de connexion.', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * [Sprint 1] Demande de réinitialisation du mot de passe.
     * Génère un jeton unique et l'envoie par email.
     */
    public function forgotPassword(Request $request)
    {
        try {
            $request->validate(['email' => 'required|email']);
            $user = User::where('email', $request->email)->first();

            if (!$user) return response()->json(['message' => 'Aucun compte associé à cet email.'], 404);

            // 1. Génération d'un token aléatoire de 64 caractères
            $token = Str::random(64);

            // 2. On sauvegarde ce token haché dans la base pour la sécurité, avec une validité de 1h
            $user->update([
                'reset_token'         => hash('sha256', $token),
                'reset_token_expires' => Carbon::now()->addHour(),
            ]);

            // 3. Construction du lien à cliquer
            $link = env('FRONTEND_URL', 'http://localhost:5173') . '/reset-password/' . $token;

            // 4. Envoi de l'email
            try {
                Mail::raw("Cliquez ici pour réinitialiser votre mot de passe (valide 1h) :\n{$link}", function($m) use ($user) {
                    $m->to($user->email)->subject('Réinitialisation du mot de passe');
                });
            } catch (\Exception $mailEx) {
                // Ignore pour ne pas bloquer si le mail plante en dev
            }

            return response()->json(['message' => 'Lien envoyé par email.']);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Erreur serveur.', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * [Sprint 1] Réinitialisation effective du mot de passe via le jeton de sécurité.
     */
    public function resetPassword(Request $request, $token)
    {
        try {
            // Validation de la complexité du nouveau mot de passe
            $request->validate([
                'mot_de_passe' => [
                    'required', 'min:8',
                    'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).+$/'
                ],
            ], [
                'mot_de_passe.required' => 'Le mot de passe est obligatoire.',
                'mot_de_passe.min'      => 'Le mot de passe doit contenir au moins 8 caractères.',
                'mot_de_passe.regex'    => 'Le mot de passe doit contenir au moins une majuscule, une minuscule, un chiffre et un caractère spécial.',
            ]);

            $hashedToken = hash('sha256', $token);

            // Recherche de l'utilisateur qui possède ce token ET dont le token n'est pas expiré
            $user = User::where('reset_token', $hashedToken)
                        ->where('reset_token_expires', '>', Carbon::now())
                        ->first();

            if (!$user) return response()->json(['message' => 'Lien invalide ou expiré.'], 400);

            // Mise à jour du mot de passe et suppression du token
            $user->update([
                'mot_de_passe'        => Hash::make($request->mot_de_passe),
                'reset_token'         => null,
                'reset_token_expires' => null,
            ]);

            return response()->json(['message' => 'Mot de passe mis à jour avec succès.']);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Erreur serveur.', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * [Sprint 1] Déconnexion (Invalidation du JWT).
     */
    public function logout()
    {
        try {
            JWTAuth::invalidate(JWTAuth::getToken()); // Ajoute le token courant dans une "blacklist"
            return response()->json(['message' => 'Déconnecté avec succès.']);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Erreur lors de la déconnexion.', 'error' => $e->getMessage()], 500);
        }
    }
}