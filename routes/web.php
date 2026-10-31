<?php

use App\Http\Controllers\Api\CatalogueController;
use App\Http\Controllers\Site\ChambreController;
use App\Http\Controllers\Site\HomeController;
use App\Http\Controllers\Site\SiteController;
use App\Http\Controllers\Site\VilleController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('accueil');
Route::get('/villes/{ville:slug}', VilleController::class)->name('ville');
Route::get('/sites/{site:slug}', SiteController::class)->name('site');
Route::get('/chambres/{chambre}', ChambreController::class)->whereNumber('chambre')->name('chambre');

// API publique du catalogue (JSON, limitée en débit, sans donnée personnelle).
Route::prefix('api')->middleware('throttle:catalogue')->group(function () {
    Route::get('/sites', [CatalogueController::class, 'sites'])->name('api.sites');
    Route::get('/chambres/disponibles', [CatalogueController::class, 'disponibles'])->name('api.chambres.disponibles');
});
