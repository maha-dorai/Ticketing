<?php

namespace Database\Factories;

use App\Models\Ticket;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Ticket>
 */
class TicketFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'titre' => fake()->sentence(6),
            'description' => fake()->paragraph(),
            'priorite' => fake()->randomElement(['BASSE', 'MOYENNE', 'HAUTE', 'CRITIQUE']),
            'etat' => fake()->randomElement(['OUVERT', 'EN_COURS', 'RESOLU', 'FERME']),
            // On laisse project_id, testeur_id, developpeur_id au seeder
            'assignment_status' => fake()->randomElement(['approved', 'pending']),
        ];
    }
}
