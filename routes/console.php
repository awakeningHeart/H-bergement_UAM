<?php

use Illuminate\Support\Facades\Schedule;

// Tâches planifiées (cron : * * * * * php artisan schedule:run). Les SMS partent par la file d'attente
// (php artisan queue:work, supervisé par Supervisor).
Schedule::command('holds:purger')->everyMinute()->withoutOverlapping();
Schedule::command('reservations:expirer')->everyTenMinutes()->withoutOverlapping();
Schedule::command('reservations:non-presentes')->everyFifteenMinutes()->withoutOverlapping();
Schedule::command('otp:purger')->dailyAt('03:00');
Schedule::command('queue:prune-failed --hours=168')->daily();
