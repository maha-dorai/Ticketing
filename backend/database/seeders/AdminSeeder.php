<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    public function run()
    {
        // Créer l'utilisateur
        $user = \App\Models\User::firstOrCreate(
            ['email' => 'admin@platform.com'],
            [
                'nom'          => 'Admin',
                'prenom'       => 'System',
                'mot_de_passe' => Hash::make('Admin@1234'),
            ]
        );

        // Créer le chef de projet associé
        $chefDeProjet = \App\Models\ChefDeProjet::firstOrCreate([
            'user_id' => $user->id
        ]);

        // Assigner le rôle d'admin au chef de projet
        \App\Models\Admin::firstOrCreate([
            'chef_de_projet_id' => $chefDeProjet->id
        ]);
    }
}