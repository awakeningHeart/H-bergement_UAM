<?php

namespace Database\Factories;

use App\Models\Ville;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<Ville> */
class VilleFactory extends Factory
{
    public function definition(): array
    {
        $nom = fake()->unique()->city();

        return [
            'nom' => $nom,
            'slug' => Str::slug($nom).'-'.fake()->unique()->numberBetween(1, 99999),
            'latitude' => fake()->latitude(11, 15),
            'longitude' => fake()->longitude(0, 8),
        ];
    }
}
