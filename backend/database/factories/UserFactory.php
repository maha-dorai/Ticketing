<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

class UserFactory extends Factory
{
    public function definition(): array
    {
        return [
            'nom'                 => fake()->lastName(),
            'prenom'              => fake()->firstName(),
            'email'               => fake()->unique()->safeEmail(),
            'mot_de_passe'        => Hash::make('password'),
            'role'                => fake()->randomElement(['testeur', 'developpeur']),
            'statut'              => 'actif',
            'github_link'         => null,
            'reset_token'         => null,
            'reset_token_expires' => null,
        ];
    }
}
