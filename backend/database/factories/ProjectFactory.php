<?php

namespace Database\Factories;

use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProjectFactory extends Factory
{
    public function definition(): array
    {
        return [
            'nom'         => 'Projet ' . fake()->words(rand(2, 3), true),
            'description' => fake()->paragraph(),
            'date_debut'  => fake()->dateTimeBetween('-6 months', '-1 months'),
            'date_fin'    => fake()->optional(0.7)->dateTimeBetween('now', '+6 months'),
            'statut'      => fake()->randomElement(['ouvert', 'en_cours', 'archive']),
        ];
    }
}