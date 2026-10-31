<?php

namespace Database\Factories;

use App\Models\Chambre;
use App\Models\ChambreHold;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<ChambreHold> */
class ChambreHoldFactory extends Factory
{
    public function definition(): array
    {
        return [
            'chambre_id' => Chambre::factory(),
            'telephone' => '+22790123456',
            'arrivee' => now()->addDays(3)->toDateString(),
            'depart' => now()->addDays(5)->toDateString(),
            'expire_le' => now()->addMinutes(15),
            'token' => Str::random(40),
        ];
    }
}
