<?php

return [
    // Stockage en UTC (config app.timezone) ; affichage et règles métier en heure de Niamey.
    'timezone' => env('UAM_TIMEZONE', 'Africa/Niamey'),

    // Langues du site public (la première est la langue par défaut ; le back-office reste en français).
    'langues' => ['fr', 'en'],

    'currency' => env('CURRENCY', 'FCFA'),
    'tva_rate' => (float) env('TVA_RATE', 0),
    'checkout_hour' => (int) env('CHECKOUT_HOUR', 12),

    'otp' => [
        'ttl_minutes' => (int) env('OTP_TTL_MINUTES', 5),
        'max_attempts' => (int) env('OTP_MAX_ATTEMPTS', 3),
        'max_sends_per_10_min' => (int) env('OTP_MAX_SENDS_PER_10_MIN', 3),
    ],

    // Chambres qu'un même visiteur (adresse IP) peut bloquer en même temps.
    'max_holds_par_ip' => (int) env('HOLDS_MAX_PAR_IP', 3),

    'hold_ttl_minutes' => (int) env('HOLD_TTL_MINUTES', 15),
    'pending_expiry_hours' => (int) env('PENDING_EXPIRY_HOURS', 24),
    'payment_deadline_hours' => (int) env('PAYMENT_DEADLINE_HOURS', 48),

    'admin' => [
        'email' => env('ADMIN_EMAIL', 'admin@uam.local'),
        'password' => env('ADMIN_PASSWORD'),
    ],

    'sms' => [
        'driver' => env('SMS_DRIVER', 'log'),
        'api_key' => env('SMS_API_KEY'),
        'sender' => env('SMS_SENDER', 'UAM'),
    ],
];
