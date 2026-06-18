<?php

// Déclaration de l'espace de noms (namespace). Cela permet à Laravel de trouver cette classe dans le dossier app/Http/Controllers.
namespace App\Http\Controllers;

// Importation du modèle User pour pouvoir interagir avec la table 'users' de la base de données.
use App\Models\User;
// Importation de la classe Request pour pouvoir lire les données envoyées par le frontend (GET, POST, etc.).
use Illuminate\Http\Request;
// Importation de la façade Hash pour pouvoir comparer les mots de passe (qui sont cryptés en base de données).
use Illuminate\Support\Facades\Hash;
// Importation de la façade Mail pour pouvoir envoyer des emails (ex: acceptation/rejet de compte).
use Illuminate\Support\Facades\Mail;
// Importation de la façade JWTAuth (Tymon) pour gérer l'authentification par Token au lieu des sessions PHP classiques.
use Tymon\JWTAuth\Facades\JWTAuth;

// La classe UserController hérite du Controller de base de Laravel. Elle gère toute la logique liée aux utilisateurs.
class UserController extends Controller
{
    // ─── [Sprint 1] GESTION DU PROFIL PERSONNEL ────────────────────────────────
    
    /**
     * [Sprint 1] Récupérer les informations du profil courant.
     * Cette fonction est appelée quand l'utilisateur va sur la page "Mon Profil".
     */
    public function getProfile()
    {
        // Utilisation d'un bloc try/catch pour éviter que l'application ne plante en cas d'erreur inattendue.
        try {
            // Demande à JWTAuth d'analyser le token (envoyé dans le header Authorization) et de récupérer l'utilisateur correspondant.
            $user = JWTAuth::parseToken()->authenticate(); 
            
            // Retourne une réponse au format JSON contenant uniquement les données non sensibles de l'utilisateur.
            return response()->json([
                'id'          => $user->id,          // L'identifiant unique
                'nom'         => $user->nom,         // Le nom de famille
                'prenom'      => $user->prenom,      // Le prénom
                'email'       => $user->email,       // L'adresse email
                'role'        => $user->role,        // Le rôle (admin, developpeur, testeur, chef_de_projet)
                'github_link' => $user->github_link, // Le lien vers le profil GitHub (s'il existe)
            ]);
        } catch (\Exception $e) {
            // Si le token est invalide ou expiré, ou s'il y a un bug serveur, on retourne une erreur 500.
            return response()->json(['message' => 'Erreur serveur.', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * [Sprint 1] Mise à jour des informations de base (Nom, Prénom).
     * Permet à un utilisateur de modifier son nom et son prénom.
     */
    public function updateProfile(Request $request)
    {
        try {
            // Récupération de l'utilisateur actuellement connecté via son Token JWT.
            $user = JWTAuth::parseToken()->authenticate();
            
            // Validation des données entrantes : le nom et le prénom sont obligatoires et doivent être des chaînes de caractères (string).
            $request->validate(['nom' => 'required|string', 'prenom' => 'required|string']);
            
            // Mise à jour de l'utilisateur dans la base de données avec les nouvelles valeurs envoyées dans la requête.
            $user->update(['nom' => $request->nom, 'prenom' => $request->prenom]);
            
            // Retourne un message de succès (Code HTTP 200 par défaut).
            return response()->json(['message' => 'Profil mis à jour.']);
        } catch (\Exception $e) {
            // En cas d'erreur (ex: base de données inaccessible), on attrape l'exception et on renvoie une erreur 500.
            return response()->json(['message' => 'Erreur serveur.', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * [Sprint 1] Changement de mot de passe depuis le profil ou forcé par l'admin.
     */
    public function changePassword(Request $request)
    {
        try {
            // Identification de l'utilisateur qui fait la requête grâce à son JWT.
            $user = JWTAuth::parseToken()->authenticate();
            
            // Validation stricte des données envoyées par le formulaire.
            $request->validate([
                // L'ancien mot de passe est requis pour des raisons de sécurité.
                'ancien_mot_de_passe'  => 'required',
                // Le nouveau mot de passe doit respecter une politique de sécurité forte (expression régulière).
                'nouveau_mot_de_passe' => [
                    'required', // Il est obligatoire
                    'min:8',    // Minimum 8 caractères
                    // Regex : au moins 1 minuscule, 1 majuscule, 1 chiffre, 1 caractère spécial.
                    'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).+$/'
                ],
            ], [
                // Messages d'erreurs personnalisés pour aider l'utilisateur si la validation échoue.
                'ancien_mot_de_passe.required'  => "L'ancien mot de passe est obligatoire.",
                'nouveau_mot_de_passe.required' => 'Le nouveau mot de passe est obligatoire.',
                'nouveau_mot_de_passe.min'      => 'Le nouveau mot de passe doit contenir au moins 8 caractères.',
                'nouveau_mot_de_passe.regex'    => 'Le mot de passe doit contenir au moins une majuscule, une minuscule, un chiffre et un caractère spécial.',
            ]);

            // Vérification de sécurité : on compare le mot de passe actuel tapé avec le hash stocké en base de données.
            if (!Hash::check($request->ancien_mot_de_passe, $user->mot_de_passe))
                // Si ça ne correspond pas, on arrête tout et on renvoie une erreur 400 (Bad Request).
                return response()->json(['message' => 'Le mot de passe actuel est incorrect.'], 400);

            // Si la vérification réussit, on met à jour le mot de passe de l'utilisateur.
            $user->update([
                // On hache le nouveau mot de passe avant de l'enregistrer (ne JAMAIS stocker en clair).
                'mot_de_passe'          => Hash::make($request->nouveau_mot_de_passe),
                // On désactive le flag qui forçait l'utilisateur à changer de mot de passe (s'il était activé).
                'force_password_change' => false,
            ]);
            
            // On renvoie un JSON de succès.
            return response()->json(['message' => 'Mot de passe modifié avec succès.']);
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            // Si la validation échoue, on récupère le premier message d'erreur et on le renvoie (Code 422 Unprocessable Entity).
            return response()->json(['message' => collect($e->errors())->flatten()->first()], 422);
        } catch (\Exception $e) {
            // Gestion des erreurs serveur générales.
            return response()->json(['message' => 'Erreur serveur.', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * [Sprint 1] Changement de l'adresse email.
     */
    public function changeEmail(Request $request)
    {
        try {
            // On identifie toujours l'utilisateur via son token.
            $user = JWTAuth::parseToken()->authenticate();
            
            // On s'assure que le nouvel email est valide et n'est pas déjà pris par un autre compte (unique:users,email).
            $request->validate([
                'new_email'    => 'required|email|unique:users,email', // L'email doit être unique dans la table 'users'.
                'mot_de_passe' => 'required', // Le mot de passe est requis pour confirmer l'identité.
            ], [
                // Message customisé si l'email existe déjà.
                'new_email.unique' => 'Cette adresse email est déjà associée à un compte.',
            ]);

            // Comme pour le changement de mot de passe, on vérifie que l'utilisateur connait son mot de passe actuel.
            if (!Hash::check($request->mot_de_passe, $user->mot_de_passe))
                // Sinon on refuse le changement.
                return response()->json(['message' => 'Le mot de passe actuel est incorrect.'], 400);

            // Mise à jour de l'email dans la base de données.
            $user->update(['email' => $request->new_email]);
            
            // Succès.
            return response()->json(['message' => 'Adresse email modifiée avec succès.']);
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            // Interception des erreurs de validation (ex: format d'email invalide).
            return response()->json(['message' => collect($e->errors())->flatten()->first()], 422);
        } catch (\Exception $e) {
            // Erreur globale.
            return response()->json(['message' => 'Erreur serveur.', 'error' => $e->getMessage()], 500);
        }
    }

    // ─── [Sprint 1] ADMINISTRATION DES UTILISATEURS ────────────────────────────

    /**
     * [Sprint 1] Récupère un utilisateur spécifique (pour la page d'édition de l'admin)
     */
    public function getUser($id)
    {
        try {
            // On effectue une requête sur la table users, mais on ne sélectionne que les champs nécessaires (pas de mot de passe).
            // La méthode findOrFail va lancer une exception (ModelNotFoundException) si l'ID n'existe pas.
            $user = User::select('id', 'nom', 'prenom', 'email', 'role', 'statut', 'github_link', 'created_at')
                        ->findOrFail($id);
            // On renvoie les données de l'utilisateur.
            return response()->json($user);
        } catch (\Exception $e) {
            // Si l'utilisateur n'est pas trouvé, on renvoie une erreur 404 (Not Found).
            return response()->json(['message' => 'Utilisateur introuvable.'], 404);
        }
    }

    /**
     * [Sprint 1 & 2] Liste tous les utilisateurs (pour le tableau de bord Admin et pour affecter aux projets)
     */
    public function getAllUsers()
    {
        try {
            // On récupère la liste complète des utilisateurs depuis la BDD, avec les colonnes choisies.
            $users = User::select('id', 'nom', 'prenom', 'email', 'role', 'statut', 'github_link', 'created_at')->get();
            // On renvoie ce grand tableau d'utilisateurs au frontend.
            return response()->json($users);
        } catch (\Exception $e) {
            // En cas de problème de connexion à la BDD, on renvoie une erreur.
            return response()->json(['message' => 'Erreur serveur.', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * [Sprint 1] Validation d'un nouveau compte (accepter/rejeter)
     * Cette fonction est utilisée par l'Admin pour modérer les inscriptions.
     */
    public function validateUser(Request $request, $id)
    {
        try {
            // La requête DOIT contenir une variable "action" valant soit 'accepter' soit 'rejeter'.
            $request->validate(['action' => 'required|in:accepter,rejeter']);
            
            // On trouve l'utilisateur cible via son ID.
            $user = User::findOrFail($id);

            // Structure conditionnelle (if/else) basée sur l'action demandée.
            if ($request->action === 'accepter') {
                // Si on accepte, le statut de l'utilisateur devient 'actif'. Il pourra se connecter.
                $user->update(['statut' => 'actif']); 
                $msg       = 'Compte activé.'; // Message pour l'Admin.
                // Corps de l'email envoyé à l'utilisateur.
                $emailBody = "Bonjour {$user->prenom},\n\nVotre demande d'accès a été acceptée.\nBienvenue !\n— L'équipe Ticketing";
                // Sujet de l'email.
                $emailSubj = "✅ Votre compte a été activé";
            } else {
                // Si on rejette, le statut devient 'rejete'. La connexion restera bloquée.
                $user->update(['statut' => 'rejete']); 
                $msg       = 'Compte rejeté.'; // Message pour l'Admin.
                // Corps de l'email de refus.
                $emailBody = "Bonjour {$user->prenom},\n\nVotre demande d'accès a été refusée.\n— L'équipe Ticketing";
                // Sujet de l'email de refus.
                $emailSubj = "❌ Demande d'accès refusée";
            }

            // On tente d'envoyer l'email généré ci-dessus de manière asynchrone (ou synchrone selon config).
            try {
                // On utilise Mail::raw pour envoyer un email au format texte simple.
                Mail::raw($emailBody, fn($m) => $m->to($user->email)->subject($emailSubj));
            } catch (\Exception $mEx) {
                // Si l'email échoue (ex: serveur SMTP non configuré), on l'ignore silencieusement pour ne pas bloquer l'application.
            }

            // On renvoie la confirmation à l'interface de l'Admin.
            return response()->json(['message' => $msg]);
        } catch (\Exception $e) {
            // Capture d'erreur serveur.
            return response()->json(['message' => 'Erreur serveur.', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * [Sprint 1] Désactivation temporaire d'un compte.
     * Cette fonction met en œuvre des règles métier strictes d'interdiction (Bannissement).
     */
    public function deactivateUser(Request $request, $id)
    {
        try {
            // L'utilisateur qui exécute l'action de désactiver (Le manager ou l'admin).
            $requester = auth()->user(); 
            // L'utilisateur cible qui va subir la désactivation.
            $user      = User::findOrFail($id); 

            // -- RÈGLES MÉTIER DE SÉCURITÉ --
            
            // Règle 1 : Un utilisateur ne peut pas s'auto-désactiver (empêche l'admin de se bloquer lui-même par erreur).
            if ($id == $requester->id)
                // Erreur 403 : Forbidden (Interdit)
                return response()->json(['message' => 'Vous ne pouvez pas désactiver votre propre compte.'], 403);

            // Règle 2 : L'administrateur système principal est immunisé, il ne peut jamais être désactivé.
            if ($user->role === 'admin')
                return response()->json(['message' => 'Impossible de désactiver un administrateur.'], 403);

            // Règle 3 : Un "Chef de projet" ne peut pas désactiver un autre "Chef de projet". 
            // Seul un "Admin" a le pouvoir hiérarchique de désactiver un Chef.
            if ($user->role === 'chef_de_projet' && $requester->role !== 'admin')
                return response()->json(['message' => 'Seul l\'administrateur peut désactiver un chef de projet.'], 403);

            // Si toutes les règles passent, on met à jour le statut en base de données.
            $user->update(['statut' => 'desactive']);
            
            // Renvoi du message de succès.
            return response()->json(['message' => 'Utilisateur désactivé avec succès.']);
        } catch (\Exception $e) {
            // En cas de crash, on retourne une erreur 500.
            return response()->json(['message' => 'Erreur serveur.', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * [Sprint 1] Réactivation d'un compte préalablement désactivé.
     */
    public function reactivateUser($id)
    {
        try {
            // On cherche l'utilisateur dans la base.
            $user = User::findOrFail($id);

            // Règle métier : On s'assure que le compte est bien dans l'état "desactive" avant de le réactiver.
            if ($user->statut !== 'desactive')
                // Si ce n'est pas le cas, c'est une requête invalide (400 Bad Request).
                return response()->json(['message' => 'Ce compte n\'est pas désactivé.'], 400);

            // On remet le statut à 'actif', permettant de nouveau la connexion JWT.
            $user->update(['statut' => 'actif']);
            
            // Succès.
            return response()->json(['message' => 'Compte réactivé avec succès.']);
        } catch (\Exception $e) {
            // En cas d'erreur de BDD, on attrape et renvoie une erreur 500.
            return response()->json(['message' => 'Erreur serveur.', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * [Sprint 1] Modification d'un compte par l'admin (édition globale d'un utilisateur existant).
     */
    public function updateUser(Request $request, $id)
    {
        try {
            // $requester est la personne qui lance la requête (l'Admin ou le Manager).
            $requester = auth()->user();
            // $user est la personne dont le profil va être modifié.
            $user      = User::findOrFail($id);

            // Règle métier hiérarchique critique :
            // Si on essaie de modifier le compte d'un Admin, OU SI on essaie de donner le rôle "admin" à quelqu'un,
            // ALORS la personne qui fait la modification ($requester) DOIT elle-même être un "admin".
            // Cela empêche un Chef de Projet d'élever ses propres droits ou de pirater le compte de l'Admin principal.
            if (
                ($user->role === 'admin' || $request->role === 'admin')
                && $requester->role !== 'admin'
            ) {
                return response()->json([
                    'message' => 'Seul un administrateur peut modifier le rôle admin.',
                ], 403);
            }

            // On valide les informations modifiées (nom, prenom, email, rôle).
            // "unique:users,email,$id" -> L'email doit être unique, SAUF pour cet utilisateur (sinon il ne pourrait pas sauvegarder s'il ne change pas d'email).
            $validated = $request->validate([
                'nom'    => 'required|string',
                'prenom' => 'required|string',
                'email'  => 'required|email|unique:users,email,' . $id,
                'role'   => 'required|in:testeur,developpeur,chef_de_projet,admin', // Le rôle doit être un des 4 rôles prévus.
            ]);
            
            // Application de la mise à jour en BDD avec les données validées.
            $user->update($validated);
            
            // Retour JSON.
            return response()->json(['message' => 'Utilisateur mis à jour.']);
        } catch (\Exception $e) {
            // Si une exception est levée, on renvoie une 500.
            return response()->json(['message' => 'Erreur serveur.', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * [Sprint 1] ⚠️ Suppression définitive INTERDITE
     * Il s'agit d'une règle métier très importante expliquée lors de la soutenance :
     * Pour préserver la traçabilité des tickets et commentaires (savoir "qui a fait quoi" même des années après), 
     * on refuse catégoriquement la suppression en base de données de l'entité User (Pas de DELETE SQL). 
     * On utilise à la place la désactivation (soft delete logique via le statut 'desactive' dans deactivateUser).
     */
    public function deleteUser($id)
    {
        // On retourne instantanément une interdiction 403, peu importe qui la demande.
        return response()->json([
            'message' => 'La suppression définitive d\'un compte est interdite. Utilisez la désactivation pour préserver la traçabilité.',
        ], 403);
    }
}