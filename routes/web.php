<?php

use App\Http\Controllers\Api\CatalogueController;
use App\Http\Controllers\Api\ClientController;
use App\Http\Controllers\Api\HoldController;
use App\Http\Controllers\Api\OtpController;
use App\Http\Controllers\Api\ReservationController;
use App\Http\Controllers\Site\ChambreController;
use App\Http\Controllers\Site\FactureController;
use App\Http\Controllers\Site\FicheController;
use App\Http\Controllers\Site\HomeController;
use App\Http\Controllers\Site\LangueController;
use App\Http\Controllers\Site\ScanController;
use App\Http\Controllers\Site\SiteController;
use App\Http\Controllers\Site\SuiviController;
use App\Http\Controllers\Site\VilleController;
use App\Http\Middleware\DefinirLangue;
use Illuminate\Support\Facades\Route;

// Site public, API et suivi : langue choisie par le visiteur (FR/EN). Les PDF et le back-office restent en français.
Route::middleware(DefinirLangue::class)->group(function () {
    Route::get('/langue/{langue}', LangueController::class)->whereAlpha('langue')->name('langue');

    Route::get('/', HomeController::class)->name('accueil');
    Route::get('/villes/{ville:slug}', VilleController::class)->name('ville');
    Route::get('/sites/{site:slug}', SiteController::class)->name('site');
    Route::get('/chambres/{chambre}', ChambreController::class)->whereNumber('chambre')->name('chambre');

    // Suivi client (sans compte) : lien opaque reçu par SMS, ou téléphone + OTP depuis l'accueil.
    Route::middleware('throttle:suivi')->group(function () {
        Route::get('/suivi/{jeton}', [SuiviController::class, 'show'])->name('suivi');
        Route::post('/suivi/{jeton}/annuler', [SuiviController::class, 'annuler'])->name('suivi.annuler');
    });

    // API publique JSON (limitée en débit, CSRF actif sur les POST ; aucune donnée personnelle avant OTP).
    Route::prefix('api')->group(function () {
        Route::middleware('throttle:catalogue')->group(function () {
            Route::get('/sites', [CatalogueController::class, 'sites'])->name('api.sites');
            Route::get('/chambres/disponibles', [CatalogueController::class, 'disponibles'])->name('api.chambres.disponibles');
        });

        Route::post('/clients/lookup', [ClientController::class, 'lookup'])->middleware('throttle:lookup')->name('api.clients.lookup');
        Route::post('/otp/send', [OtpController::class, 'send'])->middleware('throttle:otp-envoi')->name('api.otp.send');
        Route::post('/otp/verify', [OtpController::class, 'verify'])->middleware('throttle:otp-verification')->name('api.otp.verify');
        Route::post('/holds', [HoldController::class, 'store'])->middleware('throttle:holds')->name('api.holds.store');
        Route::delete('/holds', [HoldController::class, 'destroy'])->middleware('throttle:holds')->name('api.holds.destroy');
        Route::post('/reservations/estimate', [ReservationController::class, 'estimate'])->middleware('throttle:holds')->name('api.reservations.estimate');
    });

    Route::post('/reservations', [ReservationController::class, 'store'])->middleware('throttle:reservations')->name('reservations.store');
});

// Fiche PDF (QR code) : accès par lien signé uniquement.
Route::get('/fiche/{numero}/pdf', [FicheController::class, 'pdf'])->middleware(['signed', 'throttle:suivi'])->name('fiche.pdf');

// Facture / avoir PDF : lien signé pour le client, route authentifiée pour le personnel.
Route::get('/facture/{numero}/pdf', [FactureController::class, 'client'])->middleware(['signed', 'throttle:suivi'])->name('facture.pdf');
Route::get('/admin/factures/{facture}/pdf', [FactureController::class, 'personnel'])->middleware('auth')->whereNumber('facture')->name('admin.facture.pdf');

// QR code de la fiche : réservé au personnel connecté (redirige vers la connexion du back-office).
Route::get('/admin/scan/{token}', ScanController::class)->middleware('auth')->name('admin.scan');
