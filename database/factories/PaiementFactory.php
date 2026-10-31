<?php

namespace Database\Factories;

use App\Enums\ModePaiement;
use App\Models\Paiement;
use App\Models\Reservation;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Paiement> */
class PaiementFactory extends Factory
{
    public function definition(): array
    {
        return [
            'reservation_id' => Reservation::factory(),
            'montant' => 50000,
            'mode' => ModePaiement::Especes,
            'paye_le' => now(),
        ];
    }
}
