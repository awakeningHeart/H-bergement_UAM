<?php

namespace Database\Factories;

use App\Models\TypeChambre;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<TypeChambre> */
class TypeChambreFactory extends Factory
{
    public function definition(): array
    {
        return [
            'libelle' => fake()->unique()->randomElement(['Simple', 'Double', 'Suite', 'Standard', 'Familiale']).' '.fake()->unique()->numberBetween(1, 9999),
            'capacite' => fake()->numberBetween(1, 4),
            'equipements' => ['Climatisation', 'Wi-Fi', 'Douche'],
        ];
    }
}
