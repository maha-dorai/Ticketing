<?php

namespace Database\Factories;

use App\Models\Ticket;
use Illuminate\Database\Eloquent\Factories\Factory;

class TicketFactory extends Factory
{
    public function definition(): array
    {
        $etat = fake()->randomElement(['OUVERT', 'EN_COURS', 'A_TESTER', 'RECLAMATION', 'VALIDE']);
        $priorite = fake()->randomElement(['BASSE', 'MOYENNE', 'HAUTE', 'CRITIQUE']);

        return [
            'titre'             => fake()->sentence(rand(4, 8)),
            'description'       => fake()->paragraph(rand(2, 4)),
            'priorite'          => $priorite,
            'etat'              => $etat,
            'categorie_ia'      => fake()->randomElement(['BUG', 'PERFORMANCE', 'SECURITE', 'UI_UX', 'API', 'CONFIGURATION', 'AUTRE']),
            'priorite_ia'       => $priorite,
            'assignment_status' => 'approved',
            'temps_estime'      => fake()->randomFloat(1, 1, 12),
            'temps_passe'       => fake()->randomFloat(1, 0, 8),
            'type'              => 'NOUVEAU',
        ];
    }
}