<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RolesAndPermissionsSeeder::class,
            AdminSeeder::class,
            ParametresSeeder::class,
            DemoSeeder::class,
        ]);

        // Réservations fictives : jamais en production.
        if (! app()->isProduction()) {
            $this->call(DemoReservationsSeeder::class);
        }
    }
}
