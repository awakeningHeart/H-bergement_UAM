<?php

namespace App\Providers;

use Filament\Support\Facades\FilamentTimezone;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Stockage en UTC (app.timezone) ; saisie et affichage en heure de Niamey.
        FilamentTimezone::set(config('uam.timezone'));

        Model::preventLazyLoading(! $this->app->isProduction());
    }
}
