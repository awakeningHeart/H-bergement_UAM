<?php

namespace Database\Factories;

use App\Models\Chambre;
use App\Models\Site;
use App\Models\TypeChambre;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Chambre> */
class ChambreFactory extends Factory
{
    public function definition(): array
    {
        return [
            'site_id' => Site::factory(),
            'type_chambre_id' => TypeChambre::factory(),
            'numero' => (string) fake()->unique()->numberBetween(1, 99999),
            'etage' => (string) fake()->numberBetween(0, 3),
            'description' => fake()->sentence(),
            'actif' => true,
        ];
    }
}
