<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::create([
            'nom'          => 'Admin',
            'prenom'       => 'Super',
            'email'        => 'admin@ticketing.com',
            'mot_de_passe' => \Illuminate\Support\Facades\Hash::make('password'),
            'role'         => 'admin',
            'statut'       => 'actif',
        ]);

        User::create([
            'nom'          => 'Dupont',
            'prenom'       => 'Jean',
            'email'        => 'jean@dev.com',
            'mot_de_passe' => \Illuminate\Support\Facades\Hash::make('Password123!'),
            'role'         => 'developpeur',
            'statut'       => 'actif',
            'github_link'  => 'https://github.com/jeandev'
        ]);
    }
}
