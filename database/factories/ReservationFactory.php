<?php

namespace Database\Factories;

use App\Enums\Motif;
use App\Enums\Payeur;
use App\Enums\StatutReservation;
use App\Models\Chambre;
use App\Models\Client;
use App\Models\Reservation;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<Reservation> */
class ReservationFactory extends Factory
{
    public function definition(): array
    {
        $arrivee = now()->addDays(fake()->numberBetween(1, 30))->setTime(14, 0);

        return [
            'numero' => 'UAM-MH-'.now()->year.'-'.Str::padLeft((string) fake()->unique()->numberBetween(1, 99999), 5, '0'),
            'client_id' => Client::factory(),
            'chambre_id' => Chambre::factory(),
            'motif' => Motif::Mission,
            'arrivee' => $arrivee,
            'depart' => $arrivee->copy()->addDays(2)->setTime(10, 0),
            'nuitees' => 2,
            'payeur' => Payeur::Client,
            'montant_ht' => 50000,
            'montant_tva' => 0,
            'montant_ttc' => 50000,
            'statut' => StatutReservation::EnAttente,
        ];
    }

    public function statut(StatutReservation $statut): static
    {
        return $this->state(['statut' => $statut]);
    }
}
