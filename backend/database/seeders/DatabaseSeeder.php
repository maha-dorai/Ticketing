<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Project;
use App\Models\Ticket;
use App\Models\Comment;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Créer les comptes principaux de test
        $admin = User::firstOrCreate(
            ['email' => 'mehadorai3@gmail.com'],
            [
                'nom'          => 'Dorai',
                'prenom'       => 'Maha',
                'mot_de_passe' => Hash::make('Maha@1234'),
                'role'         => 'admin',
                'statut'       => 'actif',
            ]
        );

        $testeur = User::firstOrCreate(
            ['email' => 'chaimazaoui14@gmail.com'],
            [
                'nom'          => 'Zaoui',
                'prenom'       => 'Chaima',
                'mot_de_passe' => Hash::make('Chaima@1234'),
                'role'         => 'testeur',
                'statut'       => 'actif',
            ]
        );

        $dev = User::firstOrCreate(
            ['email' => 'mahadoraiii@gmail.com'],
            [
                'nom'          => 'Dorai',
                'prenom'       => 'May',
                'mot_de_passe' => Hash::make('May@1234'),
                'role'         => 'developpeur',
                'statut'       => 'actif',
                'github_link'  => 'https://github.com/maydorai'
            ]
        );

        $chef = User::firstOrCreate(
            ['email' => 'chef@gmail.com'],
            [
                'nom'          => 'Projet',
                'prenom'       => 'Chef',
                'mot_de_passe' => Hash::make('Chef@1234'),
                'role'         => 'chef_de_projet',
                'statut'       => 'actif',
            ]
        );

        // 2. Générer des utilisateurs aléatoires (15 devs, 5 testeurs, 2 admins)
        $randomUsers = User::factory(22)->create();

        // 3. Récupérer tous les développeurs et testeurs
        $allDevs = User::where('role', 'developpeur')->get();
        $allTesters = User::where('role', 'testeur')->get();

        // 4. Générer des projets (ex: 8 projets)
        $managers = User::whereIn('role', ['chef_de_projet', 'admin'])->get();
        $projects = Project::factory(8)->create([
            'created_by' => fn() => $managers->random()->id,
        ]);

        // 5. Assigner des membres et des tickets à chaque projet
        foreach ($projects as $project) {
            // Attacher aléatoirement 3 à 6 développeurs à ce projet
            $projectDevs = $allDevs->random(rand(3, 6));
            $project->users()->attach($projectDevs->pluck('id')->toArray());
            
            // Attacher aléatoirement 1 à 3 testeurs
            $projectTesters = $allTesters->random(rand(1, 3));
            $project->users()->attach($projectTesters->pluck('id')->toArray());
            
            // Ajouter notre dev et testeur principal à quelques projets (50% de chance)
            if (rand(0, 1)) $project->users()->syncWithoutDetaching([$dev->id]);
            if (rand(0, 1)) $project->users()->syncWithoutDetaching([$testeur->id]);
            if (rand(0, 1)) $project->users()->syncWithoutDetaching([$admin->id]);
            if (rand(0, 1)) $project->users()->syncWithoutDetaching([$chef->id]);

            // Récupérer les devs du projet (pour l'assignation)
            $actualProjectDevs = $project->users()->where('role', 'developpeur')->get();
            $actualProjectTesters = $project->users()->where('role', 'testeur')->get();

            // Créer 10 à 25 tickets par projet
            $numTickets = rand(10, 25);
            for ($i = 0; $i < $numTickets; $i++) {
                $ticketTester = $actualProjectTesters->random();
                
                // 80% des tickets sont assignés
                $assignedDev = null;
                $proposedDev = null;
                $status = 'pending';
                
                if (rand(1, 100) <= 80 && $actualProjectDevs->count() > 0) {
                    if (rand(0, 1)) {
                        $assignedDev = $actualProjectDevs->random()->id;
                        $status = 'approved';
                    } else {
                        $proposedDev = $actualProjectDevs->random()->id;
                        $status = 'pending';
                    }
                }

                $ticket = Ticket::factory()->create([
                    'project_id' => $project->id,
                    'testeur_id' => $ticketTester->id,
                    'developpeur_id' => $assignedDev,
                    'proposed_developpeur_id' => $proposedDev,
                    'assignment_status' => $status,
                ]);

                // Créer une notification pour l'admin si l'assignation est "pending"
                if ($status === 'pending' && $proposedDev !== null) {
                    $devModel = $actualProjectDevs->firstWhere('id', $proposedDev);
                    $msg = "🎫 Nouveau ticket « {$ticket->titre} ». Assignation proposée : {$devModel->prenom} {$devModel->nom} — en attente de votre validation.";
                    \App\Models\Notification::create([
                        'user_id' => $admin->id,
                        'message' => $msg,
                        'ticket_id' => $ticket->id,
                        'lu' => false,
                    ]);
                }

                // Ajouter des commentaires
                if (rand(0, 1)) {
                    Comment::factory(rand(1, 4))->create([
                        'ticket_id' => $ticket->id,
                        'user_id' => $project->users->random()->id
                    ]);
                }
            }
        }

        // 6. Appeler les autres seeders
        $this->call([
            PendingUsersSeeder::class,
            StatsSeeder::class,
        ]);
    }
}
