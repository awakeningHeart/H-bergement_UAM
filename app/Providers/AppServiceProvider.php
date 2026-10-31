<?php

namespace App\Providers;

use Filament\Support\Facades\FilamentTimezone;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
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

        RateLimiter::for('catalogue', fn (Request $request) => Limit::perMinute(60)->by($request->ip()));
    }
}
