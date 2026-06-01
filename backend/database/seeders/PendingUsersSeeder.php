<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class PendingUsersSeeder extends Seeder
{
    public function run()
    {
        $faker = \Faker\Factory::create('fr_FR');
        
        for ($i = 1; $i <= 6; $i++) {
            User::create([
                'nom' => $faker->lastName,
                'prenom' => $faker->firstName,
                'email' => "pending{$i}@example.com",
                'mot_de_passe' => Hash::make('password123'),
                'role' => $i % 2 === 0 ? 'developpeur' : 'testeur',
                'statut' => 'en_attente',
                'force_password_change' => false,
            ]);
        }
    }
}
