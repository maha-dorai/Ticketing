<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Project;
use App\Models\Ticket;
use Carbon\Carbon;

class StatsSeeder extends Seeder
{
    public function run()
    {
        $testeurs = User::where('role', 'testeur')->get();
        $developpeurs = User::where('role', 'developpeur')->get();
        $chefs = User::where('role', 'chef_de_projet')->get();

        if ($testeurs->isEmpty() || $developpeurs->isEmpty() || $chefs->isEmpty()) {
            $this->command->error('Veuillez créer au moins un testeur, un développeur et un chef de projet avant de lancer ce seeder.');
            return;
        }

        // Créer quelques projets s'il n'y en a pas assez
        $projects = Project::all();
        if ($projects->count() < 3) {
            $projects = collect();
            for ($i = 1; $i <= 3; $i++) {
                $p = Project::create([
                    'nom' => "Projet Généré $i",
                    'description' => "Description du projet généré $i",
                    'date_debut' => Carbon::now()->subMonths(2)->format('Y-m-d'),
                    'statut' => 'en_cours',
                    'created_by' => $chefs->random()->id,
                ]);
                // Assigner des membres
                $p->users()->sync($testeurs->pluck('id')->merge($developpeurs->pluck('id'))->random(4)->toArray());
                $projects->push($p);
            }
        }

        $etats = ['OUVERT', 'EN_COURS', 'A_TESTER', 'RECLAMATION', 'VALIDE'];
        $priorites = ['BASSE', 'MOYENNE', 'HAUTE', 'CRITIQUE'];
        $categories_ia = ['BUG', 'PERFORMANCE', 'SECURITE', 'UI_UX', 'BASE_DE_DONNEES', 'API', 'CONFIGURATION', 'AUTRE'];
        $types = ['NOUVEAU', 'RETOUR'];

        $totalTickets = 150;

        for ($i = 0; $i < $totalTickets; $i++) {
            // Random date in the last 30 days
            $created_at = Carbon::now()->subDays(rand(0, 30))->subHours(rand(0, 23))->subMinutes(rand(0, 59));
            $project = $projects->random();
            $testeur = $testeurs->random();
            $developpeur = $developpeurs->random();
            $etat = $etats[array_rand($etats)];
            
            // If it's closed/resolved, it must have been updated recently
            $updated_at = clone $created_at;
            if (in_array($etat, ['A_TESTER', 'VALIDE', 'RECLAMATION'])) {
                $updated_at->addHours(rand(2, 48)); // Resolution took between 2 and 48 hours
            }

            Ticket::create([
                'titre' => "Ticket généré #" . rand(1000, 9999),
                'description' => "Ceci est une description de ticket générée pour les statistiques.",
                'priorite' => $priorites[array_rand($priorites)],
                'etat' => $etat,
                'project_id' => $project->id,
                'testeur_id' => $testeur->id,
                'developpeur_id' => in_array($etat, ['OUVERT']) ? null : $developpeur->id,
                'assignment_status' => in_array($etat, ['OUVERT']) ? 'pending' : 'approved',
                'type' => $types[array_rand($types)],
                'categorie_ia' => $categories_ia[array_rand($categories_ia)],
                'priorite_ia' => $priorites[array_rand($priorites)],
                'created_at' => $created_at,
                'updated_at' => $updated_at,
            ]);
        }

        $this->command->info("$totalTickets tickets générés avec succès pour les statistiques !");
    }
}
