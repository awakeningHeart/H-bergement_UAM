<?php

namespace App\Http\Controllers\Site;

use App\Exceptions\ReservationException;
use App\Http\Controllers\Controller;
use App\Models\Reservation;
use App\Services\ReservationService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

/** Suivi par lien opaque : le jeton en clair n'existe que dans le SMS, seule son empreinte est en base. */
class SuiviController extends Controller
{
    public function show(string $jeton): View
    {
        return view('pages.suivi', ['reservation' => $this->trouver($jeton), 'jeton' => $jeton]);
    }

    public function annuler(string $jeton, ReservationService $reservations): RedirectResponse
    {
        $reservation = $this->trouver($jeton);

        try {
            $reservations->annuler($reservation);
        } catch (ReservationException $e) {
            return back()->withErrors(['annulation' => $e->getMessage()]);
        }

        return back()->with('statut', __('reservation.suivi.annulee'));
    }

    private function trouver(string $jeton): Reservation
    {
        return Reservation::query()
            ->with(['client', 'chambre.site.ville', 'chambre.type', 'repas', 'factures'])
            ->where('jeton_suivi_hash', ReservationService::empreinteSuivi($jeton))
            ->firstOrFail();
    }
}
