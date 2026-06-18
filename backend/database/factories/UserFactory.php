<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $roles = ['developpeur', 'developpeur', 'testeur', 'chef_de_projet', 'admin']; // Plus de chances d'avoir un developpeur
        $role = fake()->randomElement($roles);
        
        return [
            'nom' => fake()->lastName(),
            'prenom' => fake()->firstName(),
            'email' => fake()->unique()->safeEmail(),
            'mot_de_passe' => static::$password ??= Hash::make('password'),
            'role' => $role,
            'statut' => 'actif',
            'github_link' => $role === 'developpeur' ? 'https://github.com/' . fake()->userName() : null,
        ];
    }
}
