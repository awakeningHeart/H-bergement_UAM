<?php

namespace Database\Factories;

use App\Enums\TypeFacture;
use App\Models\Facture;
use App\Models\Reservation;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Facture> */
class FactureFactory extends Factory
{
    public function definition(): array
    {
        return [
            'numero' => 'FAC-'.now()->year.'-'.fake()->unique()->numerify('#####'),
            'reservation_id' => Reservation::factory(),
            'type' => TypeFacture::Facture,
            'montant_ht' => 50000,
            'montant_tva' => 0,
            'montant_ttc' => 50000,
            'emise_le' => now(),
        ];
    }
}
