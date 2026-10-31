<?php

namespace Database\Factories;

use App\Models\Site;
use App\Models\Ville;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<Site> */
class SiteFactory extends Factory
{
    public function definition(): array
    {
        $nom = 'Site '.fake()->unique()->word();

        return [
            'ville_id' => Ville::factory(),
            'nom' => $nom,
            'slug' => Str::slug($nom).'-'.fake()->unique()->numberBetween(1, 99999),
            'adresse' => fake()->streetAddress(),
            'latitude' => fake()->latitude(11, 15),
            'longitude' => fake()->longitude(0, 8),
            'description' => fake()->sentence(),
            'telephone' => '+22790000000',
            'actif' => true,
        ];
    }
}
