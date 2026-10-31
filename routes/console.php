<?php

use Illuminate\Support\Facades\Schedule;

// Tâches planifiées (cron : * * * * * php artisan schedule:run).
Schedule::command('holds:purger')->everyMinute()->withoutOverlapping();
Schedule::command('otp:purger')->dailyAt('03:00');
