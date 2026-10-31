<?php

namespace Database\Factories;

use App\Models\Organisme;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Organisme> */
class OrganismeFactory extends Factory
{
    public function definition(): array
    {
        return [
            'nom' => fake()->company(),
            'adresse' => fake()->address(),
            'telephone' => '+22796000000',
            'email' => fake()->companyEmail(),
            'contact' => fake()->name(),
        ];
    }
}
