<?php

// Le namespace indique à l'autoload de Composer où trouver cette classe (dans app/Http/Controllers).
namespace App\Http\Controllers;

// Importation du modèle Project pour interagir avec la table 'projects'
use App\Models\Project;
// Importation du modèle User pour vérifier l'état des membres
use App\Models\User;
// Importation du modèle Ticket pour compter les tickets ou vérifier leur statut lors de la clôture du projet
use App\Models\Ticket;
// Importation de la classe Mailable ProjectAssigned pour envoyer un e-mail lorsqu'un membre est ajouté
use App\Mail\ProjectAssigned;
// Classe Request pour gérer les requêtes HTTP entrantes (corps, headers, etc.)
use Illuminate\Http\Request;
// Façade Mail pour déclencher l'envoi effectif des emails
use Illuminate\Support\Facades\Mail;
// JWTAuth pour récupérer l'utilisateur qui fait la requête grâce à son jeton d'authentification
use Tymon\JWTAuth\Facades\JWTAuth;

class ProjectController extends Controller
{
    /**
     * [Sprint 2] Lister les projets
     * Cette fonction implémente le filtrage de visibilité basé sur les rôles.
     */ 
    public function index(Request $request)
    {
        try {
            // Récupération de l'utilisateur qui fait la requête via son token JWT
            $user  = JWTAuth::parseToken()->authenticate();
            
            // --- 1. LOGIQUE DE VISIBILITÉ (Règles métier cruciales) ---
            // On ne peut pas renvoyer tous les projets à tout le monde.
            
            if ($user->isAdmin()) {
                // Si c'est l'Admin, il a un passe-droit absolu : il voit absolument tous les projets.
                // On prépare une requête de base sur le modèle Project.
                $query = Project::query();
            } elseif ($user->role === 'chef_de_projet') {
                // Si c'est un Chef de projet, il ne voit QUE les projets dont il est le créateur.
                $query = Project::where('created_by', $user->id);
            } else {
                // Si c'est un Développeur ou un Testeur, il ne voit QUE les projets auxquels il a été explicitement affecté.
                // $user->projects() utilise la relation "BelongsToMany" définie dans le modèle User (table pivot project_user).
                $query = $user->projects();
            }

            // --- 2. FILTRES OPTIONNELS (Recherche) ---
            // Si la requête contient un paramètre 'search' (ex: /api/projects?search=refonte)
            if ($request->filled('search')) {
                // On ajoute une condition WHERE nom LIKE '%refonte%'
                $query->where('nom', 'like', '%' . $request->search . '%');
            }
            // Si la requête contient un filtre sur le statut (ex: /api/projects?statut=en_cours)
            if ($request->filled('statut')) {
                // On ajoute une clause d'égalité stricte
                $query->where('statut', $request->statut);
            }

            // --- 3. EAGER LOADING ET PAGINATION ---
            // 'with()' précharge les relations pour éviter le problème "N+1 queries" (très mauvais pour les performances).
            $projects = $query->with([
                // On charge les membres (users) liés au projet, mais on ne sélectionne que l'id, nom, prenom et role pour alléger la charge réseau.
                'users:id,nom,prenom,role',
                // On charge le créateur du projet
                'creator:id,nom,prenom',
            ])
            // withCount('tickets') va automatiquement créer un champ `tickets_count` contenant le nombre total de tickets du projet.
            ->withCount('tickets')
            // On trie les projets du plus récent au plus ancien.
            ->orderBy('created_at', 'desc')
            // paginate(50) retourne les résultats par page de 50 au lieu de tout charger d'un coup (protège la mémoire du serveur).
            ->paginate(50); 

            // Retourne la structure paginée en JSON.
            return response()->json($projects);
        } catch (\Exception $e) {
            // Gestion d'erreur globale
            return response()->json(['message' => 'Erreur serveur.', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * [Sprint 2] Détail d'un projet spécifique
     */
    public function show($id)
    {
        try {
            // Identifier qui demande à voir le projet.
            $user    = JWTAuth::parseToken()->authenticate();
            
            // Cherche le projet par son ID, en préchargeant les utilisateurs et le créateur.
            // S'il ne trouve pas le projet, findOrFail() lance une ModelNotFoundException qui est catchée plus bas (erreur 404).
            $project = Project::with([
                'users:id,nom,prenom,role,email',
                'creator:id,nom,prenom',
            ])->findOrFail($id);

            // --- VÉRIFICATION STRICTE DES ACCÈS ---
            // Même si le projet existe, l'utilisateur a-t-il le droit de le voir ?
            if ($user->isAdmin()) {
                // L'admin passe.
            } elseif ($user->role === 'chef_de_projet') {
                // Le chef de projet ne peut voir que s'il en est l'auteur.
                if ($project->created_by !== $user->id) {
                    return response()->json(['message' => 'Accès non autorisé'], 403);
                }
            } else {
                // Les développeurs/testeurs ne peuvent le voir que s'ils sont dans la liste des membres.
                $isMember = $project->users->contains('id', $user->id);
                if (!$isMember) {
                    return response()->json(['message' => 'Accès non autorisé'], 403);
                }
            }

            // --- [Sprint 3] Calcul dynamique de la "Charge de travail" (Workload) ---
            // Pour afficher le détail du projet, on calcule en direct le nombre de tickets "à faire" pour chaque développeur.
            // On boucle sur la collection des membres chargés.
            $project->users->each(function ($member) {
                // Requête SQL pour compter les tickets "Approuvés", assignés à ce dev, et qui ne sont ni fermés ni en réclamation.
                $member->active_tickets_count = \App\Models\Ticket::where('developpeur_id', $member->id)
                    ->where('assignment_status', 'approved')
                    ->whereIn('etat', ['OUVERT', 'EN_COURS'])
                    ->count();
            });

            // Retourne le JSON complet du projet, enrichi de 'active_tickets_count' pour chaque dev.
            return response()->json($project, 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            // L'erreur spécifique si findOrFail() a échoué (l'ID n'existe pas en base).
            return response()->json(['message' => 'Projet introuvable'], 404);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Erreur serveur.', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * [Sprint 2] Création d'un projet
     */
    public function store(Request $request)
    {
        try {
            // Le créateur est celui qui est actuellement connecté.
            $creator = JWTAuth::parseToken()->authenticate();

            // Règle métier : Seuls les "Managers" (définis comme Admin ou Chef de projet dans le modèle User) peuvent créer des projets.
            if (!$creator->isManager()) {
                return response()->json(['message' => 'Non autorisé'], 403);
            }

            // Validation des données du formulaire de création.
            $request->validate([
                'nom'         => 'required|string|max:255|unique:projects,nom', // Le nom du projet doit être unique dans l'entreprise.
                'description' => 'nullable|string',
                'date_debut'  => 'nullable|date',
                'date_fin'    => 'nullable|date|after_or_equal:date_debut', // La date de fin ne peut pas être avant le début.
                'user_ids'    => 'required|array|min:1', // Un projet doit avoir au moins un membre (le tableau user_ids ne peut pas être vide).
                'user_ids.*'  => 'exists:users,id', // Chaque ID fourni doit exister dans la table users.
            ]);

            // --- SÉCURITÉ MÉTIER : Vérification du statut des membres ---
            // Il est formellement interdit d'affecter un développeur dont le compte est "désactivé" ou "rejeté".
            $inactiveUsers = User::whereIn('id', $request->user_ids)
                                 ->where('statut', '!=', 'actif')
                                 ->pluck('email'); // On récupère l'email de ceux qui posent problème pour avertir l'utilisateur.

            // Si la liste des inactifs n'est pas vide...
            if ($inactiveUsers->isNotEmpty()) {
                return response()->json([
                    'message'         => 'Certains membres ne sont pas actifs et ne peuvent pas être affectés.',
                    'comptes_bloqués' => $inactiveUsers, // Le frontend pourra afficher exactement "qui" bloque la création.
                ], 422);
            }

            // Création physique du projet dans la base de données.
            $project = Project::create([
                'nom'         => $request->nom,
                'description' => $request->description,
                'date_debut'  => $request->date_debut ?? now()->toDateString(), // Si date vide, on prend la date d'aujourd'hui.
                'date_fin'    => $request->date_fin,
                'statut'      => 'ouvert', // Tout nouveau projet naît avec le statut 'ouvert'.
                'created_by'  => $creator->id, // L'auteur est automatiquement le manager connecté.
            ]);

            // Synchronisation de la table pivot 'project_user' pour attacher les développeurs/testeurs au projet.
            $project->users()->sync($request->user_ids);

            // --- [Sprint 4] Notifications en temps réel et Emails ---
            // On récupère les objets User de tous les gens qu'on vient d'affecter.
            $newUsers = User::whereIn('id', $request->user_ids)->get();
            foreach ($newUsers as $u) {
                // 1. Envoi de la notification Push via WebSocket (NotificationController s'occupe de Pusher et d'enregistrer en base).
                \App\Http\Controllers\NotificationController::createAndBroadcast(
                    $u->id,
                    "📁 Vous avez été ajouté(e) au projet « {$project->nom} ».",
                    null // Pas d'ID de ticket, car c'est une notification de projet.
                );
                
                // 2. Envoi d'un email de bienvenue sur ce projet.
                try {
                    Mail::to($u->email)->send(new ProjectAssigned($project, $u));
                } catch (\Exception $e) {
                    // Ignoré en silence si l'envoi d'email échoue.
                }
            }

            // Code HTTP 201 (Created)
            return response()->json(['message' => 'Projet créé avec succès.', 'project' => $project], 201);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Erreur lors de la création.', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * [Sprint 2] Modification d'un projet avec règles métier strictes d'états
     */
    public function update(Request $request, $id)
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();
            $project = Project::findOrFail($id);

            // Sécurité : Seul le créateur initial du projet a le droit de le modifier.
            if ($project->created_by !== $user->id) {
                return response()->json(['message' => "Non autorisé. Seul le créateur du projet peut le modifier."], 403);
            }

            // Validation de la requête
            $request->validate([
                'nom'    => 'required|string|max:255',
                'statut' => 'required|in:ouvert,en_cours,archive',
            ]);

            // --- RÈGLES MÉTIER SUR LES STATUTS (Machine à états) ---
            
            // Règle 1 : Empêcher de faire marche arrière. Si le projet est déjà commencé ('en_cours' ou 'archive'), on ne peut pas le repasser à 'ouvert'.
            if ($request->statut === 'ouvert' && in_array($project->statut, ['en_cours', 'archive'])) {
                return response()->json(['message' => "Un projet déjà en cours ne peut pas repasser à l'état Ouvert."], 422);
            }

            // Règle 2 : Le passage de 'ouvert' à 'en_cours' est automatique (il se déclenche dans TicketController au premier ticket). 
            // On interdit donc au manager de le faire manuellement ici pour éviter des incohérences.
            if ($request->statut === 'en_cours' && $project->statut === 'ouvert') {
                return response()->json(['message' => "Le projet passe en cours automatiquement lors de la création du premier ticket."], 422);
            }

            // Règle 3 : Clôture de projet INTERDITE s'il reste du travail à faire.
            if ($request->statut === 'archive' && $project->statut !== 'archive') {
                // On compte tous les tickets de ce projet dont l'état n'est PAS 'VALIDE'.
                $nonValideTickets = $project->tickets()->where('etat', '!=', 'VALIDE')->count();
                if ($nonValideTickets > 0) {
                    return response()->json(['message' => "Impossible de fermer le projet. $nonValideTickets ticket(s) ne sont pas validés."], 422);
                }
            }

            // On filtre la requête pour ne garder que les champs qu'on autorise à modifier.
            $updateData = $request->only('nom', 'statut', 'description', 'date_debut', 'date_fin');

            // Si le projet est archivé pour la première fois, on enregistre automatiquement la date de clôture exacte du jour.
            if ($request->statut === 'archive' && $project->statut !== 'archive') {
                $updateData['date_cloture'] = now()->toDateString();
            }

            // Exécution de la requête UPDATE en BDD.
            $project->update($updateData);

            // --- [Sprint 4] Broadcast Pusher ---
            // On prévient tous les membres du projet qu'il a été modifié.
            $members = $project->users()->pluck('users.id')->toArray();
            foreach ($members as $memberId) {
                \App\Http\Controllers\NotificationController::createAndBroadcast(
                    $memberId,
                    "📁 Le projet « {$project->nom} » a été mis à jour.",
                    null
                );
            }

            return response()->json(['message' => 'Projet mis à jour.', 'project' => $project]);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Erreur lors de la mise à jour.', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * [Sprint 2] Clôture (Archivage) rapide d'un projet.
     * C'est un raccourci utilisé par le bouton rouge "Archiver" dans le front.
     */
    public function destroy($id)
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();
            $project = Project::findOrFail($id);

            // Vérification des droits : Seul le créateur peut fermer le projet.
            if ($project->created_by !== $user->id) {
                return response()->json(['message' => "Non autorisé. Seul le créateur du projet peut le fermer."], 403);
            }

            // Règle métier stricte (idem méthode update) : on bloque si les tickets ne sont pas validés.
            $nonValideTickets = $project->tickets()->where('etat', '!=', 'VALIDE')->count();
            if ($nonValideTickets > 0) {
                return response()->json(['message' => "Impossible de fermer le projet. $nonValideTickets ticket(s) ne sont pas validés."], 422);
            }

            // On archive le projet au lieu de le supprimer physiquement (soft logic).
            $project->update(['statut' => 'archive']);

            return response()->json(['message' => 'Projet fermé (archivé) avec succès.']);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Erreur lors de la fermeture.', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * [Sprint 2] Ré-affecter des membres à un projet existant.
     * Le chef de projet peut ajuster l'équipe (ajouter ou enlever des devs/testeurs).
     */
    public function assignUsers(Request $request, $id)
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();
            $project = Project::findOrFail($id);

            // Sécurité : seul le créateur peut gérer l'équipe.
            if ($project->created_by !== $user->id) {
                return response()->json(['message' => "Non autorisé."], 403);
            }

            // Validation : On s'attend à un tableau d'IDs valides.
            $request->validate([
                'user_ids'   => 'required|array',
                'user_ids.*' => 'exists:users,id',
            ]);

            // Vérification statuts : Même règle métier que lors de la création (pas de comptes bloqués dans un projet actif).
            $inactiveUsers = User::whereIn('id', $request->user_ids)
                                 ->where('statut', '!=', 'actif')
                                 ->pluck('email');

            if ($inactiveUsers->isNotEmpty()) {
                return response()->json([
                    'message'        => 'Certains membres ne sont pas actifs et ne peuvent pas être affectés.',
                    'comptes_bloqués' => $inactiveUsers,
                ], 422);
            }

            // --- INTELLIGENCE DE NOTIFICATION ---
            // On veut éviter de spammer d'emails les anciens membres si l'équipe change peu.
            // On récupère les IDs des membres actuels.
            $oldUserIds = $project->users()->pluck('users.id')->toArray();
            // On compare les nouveaux IDs envoyés avec les anciens. 'array_diff' nous donne la liste stricte des nouveaux venus.
            $newUserIds = array_diff($request->user_ids, $oldUserIds);

            // Sync() est une méthode magique de Laravel Eloquent : elle supprime les membres qui ne sont plus dans le tableau, 
            // et insère les nouveaux. C'est parfait pour remplacer intégralement l'équipe.
            $project->users()->sync($request->user_ids);

            // On notifie UNIQUEMENT les nouveaux membres identifiés par array_diff.
            $newUsers = User::whereIn('id', $newUserIds)->get();
            foreach ($newUsers as $u) {
                \App\Http\Controllers\NotificationController::createAndBroadcast(
                    $u->id,
                    "📁 Vous avez été ajouté(e) au projet « {$project->nom} ».",
                    null
                );
                try {
                    Mail::to($u->email)->send(new ProjectAssigned($project, $u));
                } catch (\Exception $e) {}
            }

            return response()->json(['message' => 'Membres affectés avec succès.']);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Erreur lors de l\'affectation.', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * [Sprint 3] Calcule la charge de travail actuelle de chaque développeur sur ce projet.
     * Cette fonction est essentielle pour le Dashboard du Manager qui veut voir en direct la répartition des tâches.
     */
    public function getDevelopersWorkload($projectId)
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();
            
            // Seuls les Managers peuvent fouiller dans la charge de travail.
            if (!$user->isManager()) {
                return response()->json(['message' => 'Non autorisé'], 403);
            }

            $project = Project::findOrFail($projectId);
            
            // On filtre les membres du projet pour ne garder que les développeurs.
            // Puis on utilise `map` pour transformer chaque objet 'User' en un tableau de données pur.
            $developers = $project->users()->where('role', 'developpeur')->get()->map(function ($dev) {
                
                // Calcul du "Workload" (Charge) :
                // Combien de tickets sont assignés et validés pour ce développeur,
                // et qui n'ont pas encore été terminés (état OUVERT ou EN_COURS).
                $activeCount = \App\Models\Ticket::where('developpeur_id', $dev->id)
                    ->where('assignment_status', 'approved')
                    ->whereIn('etat', ['OUVERT', 'EN_COURS'])
                    ->count();
                
                // On retourne la structure JSON qui sera utilisée par les graphiques Vue.js.
                return [
                    'id' => $dev->id,
                    'nom' => $dev->nom,
                    'prenom' => $dev->prenom,
                    'statut' => $dev->statut, // Ex: 'actif', 'desactive'
                    'active_tickets_count' => $activeCount, // La jauge d'activité
                ];
            });

            return response()->json($developers, 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Erreur serveur.', 'error' => $e->getMessage()], 500);
        }
    }
}