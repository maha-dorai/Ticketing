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
    public function register(Request $request)
    {
        try {
            $request->validate([
                'nom'          => 'required|string',
                'prenom'       => 'required|string',
                'email'        => 'required|email|unique:users,email',
                'mot_de_passe' => [
                    'required', 'min:8',
                    'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).+$/'
                ],
                'role'        => 'required|in:testeur,developpeur',
                'github_link' => 'required_if:role,developpeur|required_if:role,testeur|nullable|url',
            ], [
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

            User::create([
                'nom'          => $request->nom,
                'prenom'       => $request->prenom,
                'email'        => $request->email,
                'mot_de_passe' => Hash::make($request->mot_de_passe),
                'role'         => $request->role,
                'statut'       => 'en_attente',
                'github_link'  => in_array($request->role, ['developpeur', 'testeur']) ? $request->github_link : null,
            ]);

            return response()->json(['message' => "Compte créé. En attente de validation."], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Erreur d\'inscription.', 'error' => $e->getMessage()], 500);
        }
    }

    public function login(Request $request)
    {
        try {
            $request->validate(['email' => 'required|email', 'mot_de_passe' => 'required']);

            $user = User::where('email', $request->email)->first();

            if (!$user || !Hash::check($request->mot_de_passe, $user->mot_de_passe))
                return response()->json(['message' => 'Email ou mot de passe incorrect.'], 401);

            if ($user->statut === 'en_attente')
                return response()->json(['message' => 'Votre compte est en attente de validation.'], 403);
            if ($user->statut === 'rejete')
                return response()->json(['message' => "Compte rejeté, contactez l'administrateur."], 403);
            if ($user->statut === 'desactive')
                return response()->json(['message' => "Votre compte a été désactivé."], 403);

            $token = JWTAuth::fromUser($user);

            return response()->json([
                'token' => $token,
                'user'  => [
                    'id'                    => $user->id,
                    'nom'                   => $user->nom,
                    'prenom'                => $user->prenom,
                    'email'                 => $user->email,
                    'role'                  => $user->role,
                    'force_password_change' => (bool) $user->force_password_change,
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Erreur de connexion.', 'error' => $e->getMessage()], 500);
        }
    }

    public function forgotPassword(Request $request)
    {
        try {
            $request->validate(['email' => 'required|email']);
            $user = User::where('email', $request->email)->first();

            if (!$user) return response()->json(['message' => 'Aucun compte associé à cet email.'], 404);

            $token = Str::random(64);

            $user->update([
                'reset_token'         => hash('sha256', $token),
                'reset_token_expires' => Carbon::now()->addHour(),
            ]);

            $link = env('FRONTEND_URL', 'http://localhost:5173') . '/reset-password/' . $token;

            try {
                Mail::raw("Cliquez ici pour réinitialiser votre mot de passe (valide 1h) :\n{$link}", function($m) use ($user) {
                    $m->to($user->email)->subject('Réinitialisation du mot de passe');
                });
            } catch (\Exception $mailEx) {
                // Ignore pour ne pas bloquer si le mail plante
            }

            return response()->json(['message' => 'Lien envoyé par email.']);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Erreur serveur.', 'error' => $e->getMessage()], 500);
        }
    }

    public function resetPassword(Request $request, $token)
    {
        try {
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

            $user = User::where('reset_token', $hashedToken)
                        ->where('reset_token_expires', '>', Carbon::now())
                        ->first();

            if (!$user) return response()->json(['message' => 'Lien invalide ou expiré.'], 400);

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

    public function logout()
    {
        try {
            JWTAuth::invalidate(JWTAuth::getToken());
            return response()->json(['message' => 'Déconnecté avec succès.']);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Erreur lors de la déconnexion.', 'error' => $e->getMessage()], 500);
        }
    }
}