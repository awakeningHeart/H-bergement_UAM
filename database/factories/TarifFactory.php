<?php

namespace Database\Factories;

use App\Models\Tarif;
use App\Models\TypeChambre;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Tarif> */
class TarifFactory extends Factory
{
    public function definition(): array
    {
        return [
            'type_chambre_id' => TypeChambre::factory(),
            'chambre_id' => null,
            'prix_nuit' => fake()->randomElement([15000, 25000, 35000]),
            'date_debut' => now()->subYear()->toDateString(),
            'date_fin' => null,
        ];
    }
}
