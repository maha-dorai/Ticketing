<?php

// Déclaration de l'espace de noms (namespace). Cela permet à Laravel de trouver cette classe dans le dossier app/Http/Controllers.
namespace App\Http\Controllers;

// Importation du modèle User pour pouvoir interagir avec la table 'users' de la base de données.
use App\Models\User;
// Importation de la classe Request pour pouvoir lire les données envoyées par le frontend (GET, POST, etc.).
use Illuminate\Http\Request;
// Importation de la façade Hash pour pouvoir crypter les mots de passe avant de les stocker en base.
use Illuminate\Support\Facades\Hash;
// Importation de la façade Mail pour pouvoir envoyer des emails (ex: envoi des identifiants au nouveau Chef de projet).
use Illuminate\Support\Facades\Mail;
// Importation de la façade Str (String) qui offre des fonctions utilitaires, comme la génération de chaînes aléatoires.
use Illuminate\Support\Str;

// La classe ChefDeProjetController gère spécifiquement la création et la gestion des comptes "Chef de projet" par l'Administrateur.
class ChefDeProjetController extends Controller
{
    /**
     * [Sprint 1] Créer un compte chef_de_projet.
     * Cette fonction est exclusivement appelée par l'Admin. 
     * L'admin saisit le nom, le prénom et l'email. 
     * Le système génère un mot de passe temporaire complexe et l'envoie par email.
     */
    public function create(Request $request)
    {
        // Utilisation d'un bloc try/catch pour capturer les erreurs inattendues et renvoyer un JSON propre au lieu d'une page d'erreur Laravel.
        try {
            // Validation des données entrantes. On s'assure que les champs requis sont présents et au bon format.
            $request->validate([
                'nom'    => 'required|string', // Le nom est obligatoire et doit être du texte.
                'prenom' => 'required|string', // Le prénom est obligatoire et doit être du texte.
                // L'email est obligatoire, doit avoir un format valide, et doit être unique dans la colonne 'email' de la table 'users'.
                'email'  => 'required|email|unique:users,email',
            ], [
                // Personnalisation du message d'erreur si l'email existe déjà.
                'email.unique' => 'Cette adresse email est déjà associée à un compte.',
            ]);

            // Appel d'une fonction interne (définie plus bas) pour générer un mot de passe aléatoire très sécurisé.
            $tempPassword = $this->generateSecurePassword();

            // Création de l'utilisateur en base de données via le modèle Eloquent User.
            $chef = User::create([
                'nom'                   => $request->nom, // Récupération du nom envoyé par l'admin
                'prenom'                => $request->prenom, // Récupération du prénom
                'email'                 => $request->email, // Récupération de l'email
                // On NE stocke JAMAIS un mot de passe en clair. On le hache avec bcrypt.
                'mot_de_passe'          => Hash::make($tempPassword),
                'role'                  => 'chef_de_projet', // Le rôle est forcé, l'admin ne peut pas se tromper
                'statut'                => 'actif', // Le compte est actif immédiatement (contrairement à l'inscription publique)
                // Règle métier stricte de sécurité : On force le chef de projet à changer son mot de passe à sa toute première connexion.
                'force_password_change' => true,
            ]);

            // Envoi de l'email contenant les identifiants au nouveau chef de projet.
            try {
                // Construction du texte brut de l'email.
                $body = "Bonjour {$chef->prenom},\n\n"
                      . "Un compte Chef de projet a été créé pour vous sur la plateforme Ticketing.\n\n"
                      . "Vos identifiants de connexion :\n"
                      . "Email                   : {$chef->email}\n"
                      . "Mot de passe temporaire : {$tempPassword}\n\n" // On envoie le mot de passe généré en clair ICI UNIQUEMENT.
                      . "⚠️  Vous serez obligé(e) de changer ce mot de passe lors de votre première connexion.\n\n"
                      . "— L'équipe Ticketing";

                // Envoi de l'email de façon synchrone.
                Mail::raw($body, fn($m) => $m->to($chef->email)->subject('🔐 Votre compte Chef de projet — Ticketing'));
            } catch (\Exception $mailEx) {
                // On capture l'erreur d'envoi d'email (ex: en local si le serveur SMTP n'est pas configuré).
                // On ne fait rien (pas de return), car on ne veut pas annuler la création du compte juste parce que le mail a planté.
            }

            // Retour d'une réponse de succès (code HTTP 201: Created).
            return response()->json([
                'message'        => "Compte chef de projet créé. Les identifiants ont été envoyés à {$chef->email}.",
                // On renvoie les infos du chef créé (SANS le mot de passe) pour que le frontend puisse l'ajouter à la liste affichée.
                'chef_de_projet' => [
                    'id'     => $chef->id,
                    'nom'    => $chef->nom,
                    'prenom' => $chef->prenom,
                    'email'  => $chef->email,
                ],
            ], 201);

        } catch (\Exception $e) {
            // Si une exception non interceptée survient (ex: plantage de la base de données), on renvoie une erreur 500.
            return response()->json(['message' => 'Erreur serveur.', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * [Sprint 1] Liste tous les chefs de projet.
     * Cette fonction permet à l'admin de voir son tableau de gestion des chefs.
     */
    public function list()
    {
        try {
            // Requête éloquent : on cherche tous les utilisateurs dont le rôle est STRICTEMENT 'chef_de_projet'.
            // On ne sélectionne que les colonnes utiles pour l'affichage (optimisation des performances).
            $chefs = User::where('role', 'chef_de_projet')
                         ->select('id', 'nom', 'prenom', 'email', 'statut', 'force_password_change', 'created_at')
                         ->get(); // Exécute la requête et récupère les résultats sous forme de Collection.

            // Renvoie la collection en format JSON.
            return response()->json($chefs);
        } catch (\Exception $e) {
            // Gestion des erreurs en cas de problème de connexion BDD.
            return response()->json(['message' => 'Erreur serveur.', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * [Sprint 1] Révoquer un chef de projet (désactiver son compte).
     * Règle métier de traçabilité : On ne supprime pas un chef de projet, on bloque son accès.
     */
    public function revoke($id)
    {
        try {
            // Cherche l'utilisateur qui correspond à cet ID ET qui a bien le rôle de chef_de_projet.
            // S'il ne le trouve pas (ou s'il essaie de révoquer un admin), firstOrFail() déclenche une exception 404.
            $chef = User::where('id', $id)->where('role', 'chef_de_projet')->firstOrFail();
            
            // On modifie son statut en 'desactive', l'empêchant instantanément de se reconnecter.
            $chef->update(['statut' => 'desactive']);

            // Message de succès.
            return response()->json(['message' => 'Compte chef de projet désactivé.']);
        } catch (\Exception $e) {
            // Gestion d'erreur.
            return response()->json(['message' => 'Erreur serveur.', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * [Sprint 1] Fonction utilitaire privée.
     * Génère un mot de passe sécurisé : au moins une majuscule + minuscules + chiffres + caractère spécial.
     * C'est essentiel pour s'assurer que le premier mot de passe temporaire respecte nos propres règles de validation complexes.
     */
    private function generateSecurePassword(): string
    {
        // Génère 2 lettres aléatoires et les convertit en MAJUSCULES.
        $upper   = strtoupper(Str::random(2));
        // Génère 6 lettres aléatoires en minuscules.
        $lower   = strtolower(Str::random(6));
        // Génère un nombre aléatoire entre 10 et 99 (garantit la présence de chiffres).
        $digits  = rand(10, 99);
        // Liste de caractères spéciaux autorisés.
        $specials = ['!', '@', '#', '$', '%', '^', '&', '*'];
        // Choisit un caractère spécial au hasard dans la liste.
        $special  = $specials[array_rand($specials)];

        // Concatène tous ces morceaux, puis mélange les caractères de façon aléatoire (str_shuffle) 
        // pour que la majuscule ou le chiffre ne soit pas toujours à la même place.
        return str_shuffle($upper . $lower . $digits . $special);
    }
}
