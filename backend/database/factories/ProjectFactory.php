<?php

namespace Database\Factories;

use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Project>
 */
class ProjectFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nom' => 'Projet ' . fake()->company(),
            'description' => fake()->paragraph(),
            'date_debut' => fake()->dateTimeBetween('-6 months', '-1 months'),
            'date_fin' => fake()->optional(0.7)->dateTimeBetween('now', '+6 months'),
            'statut' => fake()->randomElement(['ouvert', 'en_cours', 'archive']),
        ];
    }
}
