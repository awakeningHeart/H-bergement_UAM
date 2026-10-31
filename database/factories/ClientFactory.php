<?php

namespace Database\Factories;

use App\Models\Client;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Client> */
class ClientFactory extends Factory
{
    public function definition(): array
    {
        return [
            'telephone' => '+2279'.fake()->unique()->numerify('#######'),
            'nom' => fake()->lastName(),
            'prenom' => fake()->firstName(),
            'type_piece' => fake()->randomElement(['CNI', 'Passeport']),
            'numero_piece' => fake()->bothify('??######'),
            'piece_delivree_le' => fake()->date(),
            'email' => fake()->safeEmail(),
            'titre_fonction' => fake()->jobTitle(),
        ];
    }
}
