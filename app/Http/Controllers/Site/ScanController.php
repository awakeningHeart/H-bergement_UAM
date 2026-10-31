<?php

namespace App\Http\Controllers\Site;

use App\Filament\Resources\Reservations\ReservationResource;
use App\Http\Controllers\Controller;
use App\Models\Reservation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

/** GET /admin/scan/{token} : ouvre la fiche dans le back-office pour le personnel (RG11 respectée). */
class ScanController extends Controller
{
    public function __invoke(string $token): RedirectResponse
    {
        $numero = Reservation::numeroDepuisScan($token);
        abort_if($numero === null, 404);

        $reservation = Reservation::query()->where('numero', $numero)->firstOrFail();
        Gate::authorize('view', $reservation);

        return redirect(ReservationResource::getUrl('edit', ['record' => $reservation]));
    }
}
