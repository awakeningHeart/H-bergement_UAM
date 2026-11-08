<?php

namespace App\Providers;

use App\Http\Middleware\DefinirLangue;
use App\Services\Sms\LogSmsGateway;
use App\Services\Sms\NullSmsGateway;
use App\Services\Sms\SmsGateway;
use Filament\Support\Facades\FilamentTimezone;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(SmsGateway::class, fn () => match (config('uam.sms.driver')) {
            'null' => new NullSmsGateway,
            default => new LogSmsGateway,
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Stockage en UTC (app.timezone) ; saisie et affichage en heure de Niamey.
        FilamentTimezone::set(config('uam.timezone'));

        Model::preventLazyLoading(! $this->app->isProduction());

        // Les actions Livewire (formulaire de réservation, suivi) gardent la langue de la page d'origine.
        Livewire::addPersistentMiddleware([DefinirLangue::class]);

        RateLimiter::for('catalogue', fn (Request $request) => Limit::perMinute(60)->by($request->ip()));
        // Protection contre l'énumération de numéros et le détournement de SMS (docs/04-parcours-et-api.md).
        RateLimiter::for('lookup', fn (Request $request) => Limit::perMinute(20)->by($request->ip()));
        RateLimiter::for('otp-envoi', fn (Request $request) => [
            Limit::perMinutes(10, 3)->by('otp-tel:'.sha1((string) $request->input('telephone'))),
            Limit::perMinutes(10, 10)->by('otp-ip:'.$request->ip()),
        ]);
        RateLimiter::for('otp-verification', fn (Request $request) => Limit::perMinutes(5, 10)->by($request->ip()));
        RateLimiter::for('holds', fn (Request $request) => Limit::perMinute(20)->by($request->ip()));
        RateLimiter::for('reservations', fn (Request $request) => Limit::perMinute(5)->by($request->ip()));
        RateLimiter::for('suivi', fn (Request $request) => Limit::perMinute(30)->by($request->ip()));
    }
}
