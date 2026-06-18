<?php

namespace Database\Seeders;

use App\Models\ChefDeProjet;
use App\Models\Comment;
use App\Models\Membre;
use App\Models\Notification;
use App\Models\Project;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        Model::unguard();

        // ── 1. Chefs de projet ─────────────────────────────────────────────────
        $chefProjet1 = User::firstOrCreate(['email' => 'chef1@platform.com'], [
            'nom'          => 'Martin',
            'prenom'       => 'Lucas',
            'mot_de_passe' => Hash::make('Chef@1234'),
        ]);
        ChefDeProjet::firstOrCreate(['user_id' => $chefProjet1->id]);
        $admin = $chefProjet1; // utilisé pour les notifications

        $chefProjet2 = User::firstOrCreate(['email' => 'chef2@platform.com'], [
            'nom'          => 'Benali',
            'prenom'       => 'Sara',
            'mot_de_passe' => Hash::make('Chef@1234'),
        ]);
        ChefDeProjet::firstOrCreate(['user_id' => $chefProjet2->id]);

        // ── 2. Testeurs ────────────────────────────────────────────────────────
        $testeur1 = User::firstOrCreate(['email' => 'chaimazaoui14@gmail.com'], [
            'nom'          => 'Zaoui',
            'prenom'       => 'Chaima',
            'mot_de_passe' => Hash::make('Chaima@1234'),
        ]);
        Membre::firstOrCreate(['user_id' => $testeur1->id], [
            'role' => 'testeur', 'statut' => 'actif',
        ]);

        $testeur2 = User::firstOrCreate(['email' => 'testeur2@platform.com'], [
            'nom'          => 'Dupont',
            'prenom'       => 'Julie',
            'mot_de_passe' => Hash::make('Test@1234'),
        ]);
        Membre::firstOrCreate(['user_id' => $testeur2->id], [
            'role' => 'testeur', 'statut' => 'actif',
        ]);

        // ── 3. Développeurs ────────────────────────────────────────────────────
        $dev1 = User::firstOrCreate(['email' => 'dev1@platform.com'], [
            'nom'          => 'Laurent',
            'prenom'       => 'Nicolas',
            'mot_de_passe' => Hash::make('Dev@1234'),
        ]);
        Membre::firstOrCreate(['user_id' => $dev1->id], [
            'role' => 'developpeur', 'statut' => 'actif',
            'github_link' => 'https://github.com/nlaurent',
        ]);

        $dev2 = User::firstOrCreate(['email' => 'dev2@platform.com'], [
            'nom'          => 'Rousseau',
            'prenom'       => 'Thomas',
            'mot_de_passe' => Hash::make('Dev@1234'),
        ]);
        Membre::firstOrCreate(['user_id' => $dev2->id], [
            'role' => 'developpeur', 'statut' => 'actif',
            'github_link' => 'https://github.com/trouseau',
        ]);

        $dev3 = User::firstOrCreate(['email' => 'dev3@platform.com'], [
            'nom'          => 'Khelifi',
            'prenom'       => 'Amine',
            'mot_de_passe' => Hash::make('Dev@1234'),
        ]);
        Membre::firstOrCreate(['user_id' => $dev3->id], [
            'role' => 'developpeur', 'statut' => 'actif',
            'github_link' => 'https://github.com/akhelifi',
        ]);

        $dev4 = User::firstOrCreate(['email' => 'dev4@platform.com'], [
            'nom'          => 'Petit',
            'prenom'       => 'Emma',
            'mot_de_passe' => Hash::make('Dev@1234'),
        ]);
        Membre::firstOrCreate(['user_id' => $dev4->id], [
            'role' => 'developpeur', 'statut' => 'actif',
            'github_link' => 'https://github.com/epetit',
        ]);

        // ── 4. Comptes en attente / rejeté ─────────────────────────────────────
        $pending1 = User::firstOrCreate(['email' => 'pending1@platform.com'], [
            'nom'          => 'Bernard',
            'prenom'       => 'Alex',
            'mot_de_passe' => Hash::make('Pending@1234'),
        ]);
        Membre::firstOrCreate(['user_id' => $pending1->id], [
            'role' => 'developpeur', 'statut' => 'en_attente',
            'github_link' => 'https://github.com/abernard',
        ]);

        $pending2 = User::firstOrCreate(['email' => 'pending2@platform.com'], [
            'nom'          => 'Moreau',
            'prenom'       => 'Léa',
            'mot_de_passe' => Hash::make('Pending@1234'),
        ]);
        Membre::firstOrCreate(['user_id' => $pending2->id], [
            'role' => 'testeur', 'statut' => 'en_attente',
        ]);

        $rejected = User::firstOrCreate(['email' => 'rejected@platform.com'], [
            'nom'          => 'Garnier',
            'prenom'       => 'Paul',
            'mot_de_passe' => Hash::make('Rejected@1234'),
        ]);
        Membre::firstOrCreate(['user_id' => $rejected->id], [
            'role' => 'developpeur', 'statut' => 'rejete',
            'github_link' => 'https://github.com/pgarnier',
        ]);


        $project1 = Project::firstOrCreate(['nom' => 'Refonte du site e-commerce'], [
            'description' => 'Refonte complète de la plateforme de vente en ligne : nouvelle UX, paiement sécurisé, intégration des stocks en temps réel.',
            'statut'      => 'en_cours',
            'date_debut'  => now()->subMonths(3),
            'date_fin'    => now()->addMonths(2),
            'created_by' => $chefProjet1->id,
        ]);

        $project2 = Project::firstOrCreate(['nom' => 'Application mobile RH'], [
            'description' => 'Application mobile pour la gestion des congés, notes de frais et fiches de paie des employés.',
            'statut'      => 'en_cours',
            'date_debut'  => now()->subMonths(2),
            'date_fin'    => now()->addMonths(4),
            'created_by' => $chefProjet1->id,
        ]);

        $project3 = Project::firstOrCreate(['nom' => 'Tableau de bord analytique'], [
            'description' => 'Dashboard temps réel pour visualiser les KPIs métier : ventes, performances équipes, alertes automatiques.',
            'statut'      => 'en_cours',
            'date_debut'  => now()->subMonth(),
            'date_fin'    => now()->addMonths(3),
            'created_by' => $chefProjet2->id,
        ]);

        $project4 = Project::firstOrCreate(['nom' => 'API de paiement v2'], [
            'description' => 'Refactoring complet de l\'API de paiement pour supporter Stripe, PayPal et les virements SEPA.',
            'statut'      => 'ouvert',
            'date_debut'  => now()->subWeeks(2),
            'date_fin'    => now()->addMonths(5),
            'created_by' => $chefProjet2->id,
        ]);

        $project5 = Project::firstOrCreate(['nom' => 'Portail client self-service'], [
            'description'   => 'Portail en ligne permettant aux clients de gérer leurs abonnements, factures et tickets de support.',
            'statut'        => 'archive',
            'date_debut'    => now()->subMonths(8),
            'date_fin'      => now()->subMonths(1),
            'date_cloture'  => now()->subMonths(1),
            'created_by'    => $chefProjet1->id,
        ]);

        $projects = [$project1, $project2, $project3, $project4, $project5];

        // ── 3. Membres des projets ─────────────────────────────────────────────

        // Projet 1 — refonte e-commerce
        $project1->users()->syncWithoutDetaching([$dev1->id, $dev2->id, $dev3->id, $testeur1->id, $chefProjet1->id]);
        // Projet 2 — mobile RH
        $project2->users()->syncWithoutDetaching([$dev2->id, $dev4->id, $testeur2->id, $chefProjet1->id]);
        // Projet 3 — dashboard
        $project3->users()->syncWithoutDetaching([$dev1->id, $dev3->id, $dev4->id, $testeur1->id, $testeur2->id, $chefProjet2->id]);
        // Projet 4 — API paiement
        $project4->users()->syncWithoutDetaching([$dev1->id, $dev2->id, $testeur1->id, $chefProjet2->id]);
        // Projet 5 — portail client (archivé)
        $project5->users()->syncWithoutDetaching([$dev3->id, $dev4->id, $testeur2->id, $chefProjet1->id]);

        // ── 4. Tickets réalistes par projet ───────────────────────────────────

        $this->seedProject1Tickets($project1, $dev1, $dev2, $dev3, $testeur1, $admin);
        $this->seedProject2Tickets($project2, $dev2, $dev4, $testeur2, $admin);
        $this->seedProject3Tickets($project3, $dev1, $dev3, $dev4, $testeur1, $testeur2, $admin);
        $this->seedProject4Tickets($project4, $dev1, $dev2, $testeur1, $admin);

        Model::reguard();
    }

    // ── Projet 1 : Refonte e-commerce ─────────────────────────────────────────
    private function seedProject1Tickets($project, $dev1, $dev2, $dev3, $testeur, $admin): void
    {
        $tickets = [
            [
                'titre'             => 'Page panier ne se charge pas sur mobile',
                'description'       => "Sur iOS Safari, la page panier reste blanche après l'ajout d'un article. Le problème est reproductible à 100% sur iPhone 13 avec iOS 16. Les logs console montrent une erreur JavaScript : 'Cannot read property of undefined'.",
                'priorite'          => 'CRITIQUE',
                'etat'              => 'EN_COURS',
                'categorie_ia'      => 'BUG',
                'priorite_ia'       => 'CRITIQUE',
                'solution_ia'       => 'Vérifier la gestion des états null dans le composant Cart. Ajouter des guards sur les propriétés optionnelles.',
                'developpeur_id'    => $dev1->id,
                'assignment_status' => 'approved',
                'temps_estime'      => 4,
                'temps_passe'       => 2.5,
                'type'              => 'NOUVEAU',
            ],
            [
                'titre'             => 'Erreur 500 lors du paiement par carte',
                'description'       => "Le bouton \"Payer\" renvoie une erreur 500 pour certains utilisateurs. Le problème semble lié à la validation côté serveur du CVV. Affecte environ 15% des transactions.",
                'priorite'          => 'CRITIQUE',
                'etat'              => 'OUVERT',
                'categorie_ia'      => 'BUG',
                'priorite_ia'       => 'CRITIQUE',
                'solution_ia'       => 'Vérifier la validation du CVV dans le contrôleur PaymentController. Logger les paramètres entrants pour identifier le cas problématique.',
                'developpeur_id'    => null,
                'proposed_developpeur_id' => $dev2->id,
                'assignment_status' => 'pending',
                'temps_estime'      => 6,
                'temps_passe'       => 0,
                'type'              => 'NOUVEAU',
            ],
            [
                'titre'             => 'Optimiser les images de la page d\'accueil',
                'description'       => 'Les images hero de la page d\'accueil ne sont pas compressées et ralentissent le chargement initial (LCP > 4s). Mettre en place du lazy loading et convertir en WebP.',
                'priorite'          => 'HAUTE',
                'etat'              => 'A_TESTER',
                'categorie_ia'      => 'PERFORMANCE',
                'priorite_ia'       => 'HAUTE',
                'solution_ia'       => 'Utiliser next/image ou sharp pour la conversion WebP. Ajouter loading="lazy" sur les images below-the-fold.',
                'developpeur_id'    => $dev2->id,
                'assignment_status' => 'approved',
                'temps_estime'      => 3,
                'temps_passe'       => 3,
                'type'              => 'NOUVEAU',
            ],
            [
                'titre'             => 'Le filtre de prix ne fonctionne pas correctement',
                'description'       => 'Le slider de prix sur la page catalogue retourne des résultats incohérents. Filtrer entre 50€ et 100€ affiche des articles à 150€.',
                'priorite'          => 'HAUTE',
                'etat'              => 'RECLAMATION',
                'categorie_ia'      => 'BUG',
                'priorite_ia'       => 'HAUTE',
                'solution_ia'       => 'Vérifier la logique de filtrage dans ProductController@index. Le problème est probablement dans la construction de la requête SQL.',
                'developpeur_id'    => $dev3->id,
                'assignment_status' => 'approved',
                'temps_estime'      => 2,
                'temps_passe'       => 2,
                'raison_reclamation' => 'Le bug est toujours présent après le déploiement. Le filtre 50-100€ affiche encore un article Xiaomi à 189€.',
                'type'              => 'NOUVEAU',
            ],
            [
                'titre'             => 'Ajouter la recherche par code promo',
                'description'       => 'Implémenter un champ de saisie de code promo dans le panier avec validation en temps réel via l\'API. Afficher la remise appliquée.',
                'priorite'          => 'MOYENNE',
                'etat'              => 'OUVERT',
                'categorie_ia'      => 'API',
                'priorite_ia'       => 'MOYENNE',
                'solution_ia'       => 'Créer un endpoint POST /api/coupons/validate. Utiliser debounce pour limiter les appels API.',
                'developpeur_id'    => null,
                'assignment_status' => 'none',
                'temps_estime'      => 5,
                'temps_passe'       => 0,
                'type'              => 'NOUVEAU',
            ],
            [
                'titre'             => 'Mettre à jour la politique de confidentialité RGPD',
                'description'       => 'La bannière cookie ne répond pas aux exigences RGPD 2024. Il manque le refus granulaire par catégorie et l\'historique des consentements.',
                'priorite'          => 'HAUTE',
                'etat'              => 'VALIDE',
                'categorie_ia'      => 'SECURITE',
                'priorite_ia'       => 'HAUTE',
                'solution_ia'       => 'Intégrer une librairie de gestion des consentements (ex: Axeptio). Stocker les préférences utilisateur en base.',
                'developpeur_id'    => $dev1->id,
                'assignment_status' => 'approved',
                'temps_estime'      => 4,
                'temps_passe'       => 4.5,
                'type'              => 'NOUVEAU',
            ],
            [
                'titre'             => 'Fiche produit — images ne se chargent plus',
                'description'       => 'Suite au déploiement de vendredi, les images des fiches produit s\'affichent en 404. Le CDN retourne des erreurs pour toutes les URLs `/products/images/`.',
                'priorite'          => 'CRITIQUE',
                'etat'              => 'EN_COURS',
                'categorie_ia'      => 'BUG',
                'priorite_ia'       => 'CRITIQUE',
                'solution_ia'       => 'Vérifier la configuration du CDN et les règles de réécriture d\'URL. Rollback possible si le problème persiste.',
                'developpeur_id'    => $dev2->id,
                'assignment_status' => 'approved',
                'temps_estime'      => 2,
                'temps_passe'       => 0.5,
                'type'              => 'NOUVEAU',
            ],
            [
                'titre'             => 'Ajouter les avis clients sur la page produit',
                'description'       => 'Intégrer un système de notation 5 étoiles avec commentaires vérifiés. Les avis doivent être filtrables et paginés.',
                'priorite'          => 'BASSE',
                'etat'              => 'OUVERT',
                'categorie_ia'      => 'UI_UX',
                'priorite_ia'       => 'BASSE',
                'solution_ia'       => 'Créer les migrations Review et Rating. Implémenter un endpoint REST et le composant frontend avec pagination.',
                'developpeur_id'    => null,
                'assignment_status' => 'none',
                'temps_estime'      => 8,
                'temps_passe'       => 0,
                'type'              => 'NOUVEAU',
            ],
        ];

        foreach ($tickets as $data) {
            $ticket = Ticket::create(array_merge($data, [
                'project_id' => $project->id,
                'created_by' => $testeur->id,
            ]));

            $this->addComments($ticket, $project);

            if ($data['assignment_status'] === 'pending' && isset($data['proposed_developpeur_id'])) {
                Notification::create([
                    'user_id'   => $admin->id,
                    'message'   => "Nouveau ticket « {$ticket->titre} » — assignation proposée en attente de validation.",
                    'ticket_id' => $ticket->id,
                    'lu'        => false,
                ]);
            }
        }
    }

    // ── Projet 2 : Application mobile RH ──────────────────────────────────────
    private function seedProject2Tickets($project, $dev2, $dev4, $testeur, $admin): void
    {
        $tickets = [
            [
                'titre'             => 'Crash au lancement sur Android 14',
                'description'       => 'L\'application plante immédiatement au démarrage sur les appareils Android 14 (SDK 34). Le crash log indique une incompatibilité avec les nouvelles permissions de notification.',
                'priorite'          => 'CRITIQUE',
                'etat'              => 'EN_COURS',
                'categorie_ia'      => 'BUG',
                'priorite_ia'       => 'CRITIQUE',
                'solution_ia'       => 'Mettre à jour le targetSdkVersion à 34 et adapter la demande de permissions POST_NOTIFICATIONS.',
                'developpeur_id'    => $dev2->id,
                'assignment_status' => 'approved',
                'temps_estime'      => 3,
                'temps_passe'       => 1.5,
                'type'              => 'NOUVEAU',
            ],
            [
                'titre'             => 'Le formulaire de demande de congé ne sauvegarde pas',
                'description'       => 'Après avoir rempli le formulaire de demande de congé et cliqué sur "Envoyer", les données ne sont pas enregistrées. Aucune erreur visible pour l\'utilisateur.',
                'priorite'          => 'HAUTE',
                'etat'              => 'A_TESTER',
                'categorie_ia'      => 'BUG',
                'priorite_ia'       => 'HAUTE',
                'solution_ia'       => 'Vérifier le handler du formulaire et le mapping des champs date. Le token CSRF est peut-être expiré.',
                'developpeur_id'    => $dev4->id,
                'assignment_status' => 'approved',
                'temps_estime'      => 4,
                'temps_passe'       => 4,
                'type'              => 'NOUVEAU',
            ],
            [
                'titre'             => 'Ajouter la signature électronique des documents',
                'description'       => 'Les contrats et avenants doivent pouvoir être signés directement depuis l\'application. Intégrer DocuSign ou une alternative.',
                'priorite'          => 'MOYENNE',
                'etat'              => 'OUVERT',
                'categorie_ia'      => 'API',
                'priorite_ia'       => 'MOYENNE',
                'solution_ia'       => 'Évaluer l\'API DocuSign et HelloSign. Créer un service de signature avec webhook de confirmation.',
                'developpeur_id'    => null,
                'assignment_status' => 'none',
                'temps_estime'      => 10,
                'temps_passe'       => 0,
                'type'              => 'NOUVEAU',
            ],
            [
                'titre'             => 'Synchronisation calendrier Google défaillante',
                'description'       => 'Les congés approuvés ne s\'affichent pas dans le calendrier Google de l\'employé. Le webhook de synchronisation semble ne pas se déclencher.',
                'priorite'          => 'HAUTE',
                'etat'              => 'OUVERT',
                'categorie_ia'      => 'API',
                'priorite_ia'       => 'HAUTE',
                'solution_ia'       => 'Vérifier les logs du webhook Google Calendar. Tester le refresh token OAuth2 et la propagation des événements.',
                'developpeur_id'    => null,
                'proposed_developpeur_id' => $dev2->id,
                'assignment_status' => 'pending',
                'temps_estime'      => 5,
                'temps_passe'       => 0,
                'type'              => 'NOUVEAU',
            ],
            [
                'titre'             => 'Notification push non reçue pour les approbations',
                'description'       => 'Quand un manager approuve ou refuse une demande de congé, l\'employé ne reçoit pas de notification push. La notification apparaît dans l\'appli mais pas en push.',
                'priorite'          => 'MOYENNE',
                'etat'              => 'VALIDE',
                'categorie_ia'      => 'BUG',
                'priorite_ia'       => 'MOYENNE',
                'developpeur_id'    => $dev4->id,
                'assignment_status' => 'approved',
                'temps_estime'      => 3,
                'temps_passe'       => 2.5,
                'type'              => 'NOUVEAU',
            ],
            [
                'titre'             => 'Dark mode — texte illisible sur les formulaires',
                'description'       => 'En mode sombre, le texte des champs de saisie est noir sur fond noir. Affecte tous les formulaires de l\'application.',
                'priorite'          => 'HAUTE',
                'etat'              => 'RECLAMATION',
                'categorie_ia'      => 'UI_UX',
                'priorite_ia'       => 'HAUTE',
                'developpeur_id'    => $dev2->id,
                'assignment_status' => 'approved',
                'temps_estime'      => 2,
                'temps_passe'       => 2,
                'raison_reclamation' => 'Le problème persiste sur les champs de type "select". Seuls les inputs text sont corrigés.',
                'type'              => 'NOUVEAU',
            ],
        ];

        foreach ($tickets as $data) {
            $ticket = Ticket::create(array_merge($data, [
                'project_id' => $project->id,
                'created_by' => $testeur->id,
            ]));

            $this->addComments($ticket, $project);

            if (($data['assignment_status'] ?? '') === 'pending' && isset($data['proposed_developpeur_id'])) {
                Notification::create([
                    'user_id'   => $admin->id,
                    'message'   => "Nouveau ticket « {$ticket->titre} » — assignation proposée en attente de validation.",
                    'ticket_id' => $ticket->id,
                    'lu'        => false,
                ]);
            }
        }
    }

    // ── Projet 3 : Dashboard analytique ───────────────────────────────────────
    private function seedProject3Tickets($project, $dev1, $dev3, $dev4, $testeur1, $testeur2, $admin): void
    {
        $tickets = [
            [
                'titre'             => 'Graphique des ventes — données incorrectes pour décembre',
                'description'       => 'Le graphique mensuel affiche des ventes à 0 pour décembre alors que la base de données contient des données. Le problème semble lié au changement d\'année dans la requête.',
                'priorite'          => 'HAUTE',
                'etat'              => 'EN_COURS',
                'categorie_ia'      => 'BUG',
                'priorite_ia'       => 'HAUTE',
                'solution_ia'       => 'Vérifier la requête SQL pour la gestion du changement d\'année. Probablement un problème avec YEAR() et MONTH() combinés.',
                'developpeur_id'    => $dev1->id,
                'assignment_status' => 'approved',
                'temps_estime'      => 3,
                'temps_passe'       => 1,
                'type'              => 'NOUVEAU',
                'created_by_override' => $testeur1->id,
            ],
            [
                'titre'             => 'Le dashboard met plus de 10 secondes à se charger',
                'description'       => 'La page principale du tableau de bord prend entre 8 et 15 secondes pour afficher les données. Les requêtes SQL ne sont pas optimisées et lancées séquentiellement.',
                'priorite'          => 'CRITIQUE',
                'etat'              => 'OUVERT',
                'categorie_ia'      => 'PERFORMANCE',
                'priorite_ia'       => 'CRITIQUE',
                'solution_ia'       => 'Paralléliser les requêtes. Ajouter des indexes sur les colonnes filtrées. Implémenter un cache Redis avec TTL de 5 minutes.',
                'developpeur_id'    => null,
                'proposed_developpeur_id' => $dev3->id,
                'assignment_status' => 'pending',
                'temps_estime'      => 8,
                'temps_passe'       => 0,
                'type'              => 'NOUVEAU',
                'created_by_override' => $testeur1->id,
            ],
            [
                'titre'             => 'Export Excel corrompu pour les rapports > 10 000 lignes',
                'description'       => 'Lorsque l\'export Excel dépasse 10 000 lignes, le fichier généré est corrompu et ne peut pas être ouvert. Les exports < 5 000 lignes fonctionnent correctement.',
                'priorite'          => 'HAUTE',
                'etat'              => 'A_TESTER',
                'categorie_ia'      => 'BUG',
                'priorite_ia'       => 'HAUTE',
                'solution_ia'       => 'Utiliser le chunking dans Laravel Excel. Écrire par lots de 1000 lignes avec le mode streaming.',
                'developpeur_id'    => $dev4->id,
                'assignment_status' => 'approved',
                'temps_estime'      => 5,
                'temps_passe'       => 5,
                'type'              => 'NOUVEAU',
                'created_by_override' => $testeur2->id,
            ],
            [
                'titre'             => 'Ajouter un filtre par période personnalisée',
                'description'       => 'Permettre aux utilisateurs de sélectionner une plage de dates personnalisée (date picker) pour filtrer toutes les métriques du dashboard.',
                'priorite'          => 'MOYENNE',
                'etat'              => 'OUVERT',
                'categorie_ia'      => 'UI_UX',
                'priorite_ia'       => 'MOYENNE',
                'solution_ia'       => 'Intégrer un composant DateRangePicker. Propager les paramètres start_date et end_date dans tous les endpoints.',
                'developpeur_id'    => null,
                'assignment_status' => 'none',
                'temps_estime'      => 6,
                'temps_passe'       => 0,
                'type'              => 'NOUVEAU',
                'created_by_override' => $testeur2->id,
            ],
            [
                'titre'             => 'Les alertes email ne partent pas la nuit',
                'description'       => 'Les alertes programmées pour être envoyées à minuit (seuils de ventes) ne sont pas déclenchées. La tâche cron semble ne pas fonctionner la nuit en production.',
                'priorite'          => 'HAUTE',
                'etat'              => 'VALIDE',
                'categorie_ia'      => 'CONFIGURATION',
                'priorite_ia'       => 'HAUTE',
                'solution_ia'       => 'Vérifier la configuration du scheduler Laravel en production. Ajouter le log des tâches cron exécutées.',
                'developpeur_id'    => $dev1->id,
                'assignment_status' => 'approved',
                'temps_estime'      => 2,
                'temps_passe'       => 2,
                'type'              => 'NOUVEAU',
                'created_by_override' => $testeur1->id,
            ],
        ];

        foreach ($tickets as $data) {
            $testeurId = $data['created_by_override'] ?? $testeur1->id;
            unset($data['created_by_override']);

            $ticket = Ticket::create(array_merge($data, [
                'project_id' => $project->id,
                'created_by' => $testeurId,
            ]));

            $this->addComments($ticket, $project);

            if (($data['assignment_status'] ?? '') === 'pending' && isset($data['proposed_developpeur_id'])) {
                Notification::create([
                    'user_id'   => $admin->id,
                    'message'   => "Nouveau ticket « {$ticket->titre} » — assignation proposée en attente de validation.",
                    'ticket_id' => $ticket->id,
                    'lu'        => false,
                ]);
            }
        }
    }

    // ── Projet 4 : API paiement v2 ─────────────────────────────────────────────
    private function seedProject4Tickets($project, $dev1, $dev2, $testeur, $admin): void
    {
        $tickets = [
            [
                'titre'             => 'Implémenter le webhook Stripe pour les remboursements',
                'description'       => 'Stripe envoie des événements `charge.refunded` que l\'API ne traite pas encore. Les remboursements doivent mettre à jour le statut de la commande en base.',
                'priorite'          => 'HAUTE',
                'etat'              => 'EN_COURS',
                'categorie_ia'      => 'API',
                'priorite_ia'       => 'HAUTE',
                'solution_ia'       => 'Créer un endpoint /webhooks/stripe. Vérifier la signature Stripe-Signature. Dispatcher un job pour le traitement.',
                'developpeur_id'    => $dev1->id,
                'assignment_status' => 'approved',
                'temps_estime'      => 6,
                'temps_passe'       => 3,
                'type'              => 'NOUVEAU',
            ],
            [
                'titre'             => 'Faille XSS dans le formulaire de facturation',
                'description'       => 'Le champ "Nom sur la carte" n\'est pas échappé côté serveur. Il est possible d\'injecter du HTML/JS dans les emails de confirmation générés.',
                'priorite'          => 'CRITIQUE',
                'etat'              => 'OUVERT',
                'categorie_ia'      => 'SECURITE',
                'priorite_ia'       => 'CRITIQUE',
                'solution_ia'       => 'Appliquer strip_tags() et htmlspecialchars() sur tous les champs texte utilisateur avant insertion. Ajouter une politique CSP stricte.',
                'developpeur_id'    => null,
                'proposed_developpeur_id' => $dev2->id,
                'assignment_status' => 'pending',
                'temps_estime'      => 4,
                'temps_passe'       => 0,
                'type'              => 'NOUVEAU',
            ],
            [
                'titre'             => 'Ajouter le support des virements SEPA',
                'description'       => 'Intégrer le mode de paiement SEPA Direct Debit via Stripe. Gérer les mandats, délais de traitement et notifications d\'échec.',
                'priorite'          => 'MOYENNE',
                'etat'              => 'OUVERT',
                'categorie_ia'      => 'API',
                'priorite_ia'       => 'MOYENNE',
                'solution_ia'       => 'Utiliser stripe.paymentMethods.create avec type sepa_debit. Gérer les statuts pending et failed avec des webhooks.',
                'developpeur_id'    => null,
                'assignment_status' => 'none',
                'temps_estime'      => 12,
                'temps_passe'       => 0,
                'type'              => 'NOUVEAU',
            ],
            [
                'titre'             => 'Les logs de transaction ne sont pas structurés',
                'description'       => 'Les logs de paiement sont en texte brut, impossibles à parser. Ils doivent être en JSON structuré avec transaction_id, amount, currency, status et timestamp.',
                'priorite'          => 'BASSE',
                'etat'              => 'A_TESTER',
                'categorie_ia'      => 'CONFIGURATION',
                'priorite_ia'       => 'BASSE',
                'solution_ia'       => 'Utiliser un channel de log dédié dans config/logging.php. Créer un formatter JSON personnalisé.',
                'developpeur_id'    => $dev2->id,
                'assignment_status' => 'approved',
                'temps_estime'      => 3,
                'temps_passe'       => 3,
                'type'              => 'NOUVEAU',
            ],
        ];

        foreach ($tickets as $data) {
            $ticket = Ticket::create(array_merge($data, [
                'project_id' => $project->id,
                'created_by' => $testeur->id,
            ]));

            $this->addComments($ticket, $project);

            if (($data['assignment_status'] ?? '') === 'pending' && isset($data['proposed_developpeur_id'])) {
                Notification::create([
                    'user_id'   => $admin->id,
                    'message'   => "Nouveau ticket « {$ticket->titre} » — assignation proposée en attente de validation.",
                    'ticket_id' => $ticket->id,
                    'lu'        => false,
                ]);
            }
        }
    }

    // ── Commentaires réalistes ────────────────────────────────────────────────
    private function addComments(Ticket $ticket, Project $project): void
    {
        $commentaires = [
            'Je reproduis le problème sur ma machine, je prends en charge.',
            'Après investigation, le problème vient du middleware de validation. Je travaille sur un fix.',
            'PR soumise, en attente de review.',
            'Fix déployé sur la branche dev, peut-on valider en staging ?',
            'Testé sur staging — le problème est résolu. Merci !',
            'Il y a un cas limite non traité : que se passe-t-il si l\'utilisateur est déconnecté pendant l\'opération ?',
            'Bonne remarque, j\'ajoute la gestion du cas déconnexion.',
            'La correction introduit une régression sur la page des paramètres, je regarde.',
            'Régression corrigée. Nouvelle PR soumise.',
            'Confirmé en production, le ticket peut être fermé.',
        ];

        $users = $project->users;
        if ($users->isEmpty()) return;

        $count = rand(0, 3);
        for ($i = 0; $i < $count; $i++) {
            Comment::create([
                'ticket_id' => $ticket->id,
                'user_id'   => $users->random()->id,
                'contenu'   => $commentaires[array_rand($commentaires)],
            ]);
        }
    }
}