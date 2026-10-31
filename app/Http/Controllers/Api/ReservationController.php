<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\ReservationException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Reservation\SejourRequest;
use App\Http\Requests\Reservation\StoreReservationRequest;
use App\Models\Chambre;
use App\Services\ReservationService;
use App\Support\Telephone;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;

class ReservationController extends Controller
{
    /** POST /api/reservations/estimate : calcul HT/TVA/TTC. */
    public function estimate(Request $request, ReservationService $reservations): JsonResponse
    {
        $donnees = $request->validate(SejourRequest::regles() + []);
        $chambre = Chambre::query()->findOrFail($donnees['chambre_id']);

        try {
            $devis = $reservations->estimer($chambre, $donnees['arrivee'], $donnees['heure'], $donnees['depart'], $donnees['repas'] ?? []);
        } catch (ReservationException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json($devis);
    }

    /** POST /reservations : création finale, transactionnelle et idempotente. */
    public function store(StoreReservationRequest $request, ReservationService $reservations): JsonResponse
    {
        $d = $request->validated();

        try {
            $resultat = $reservations->creer([
                'telephone' => Telephone::normaliser($d['telephone']),
                'identite' => $d,
                'sejour' => $d,
                'facturation' => $d,
                'signature' => $d['signature'],
                'idempotence' => $d['idempotence'] ?? null,
            ], $d['hold_token']);
        } catch (ReservationException $e) {
            $statut = match ($e->raison) {
                ReservationException::VERIFICATION => 403,
                default => 409,
            };

            return response()->json(['message' => $e->getMessage(), 'raison' => $e->raison], $statut);
        }

        $reservation = $resultat['reservation'];

        return response()->json([
            'numero' => $reservation->numero,
            'statut' => $reservation->statut->value,
            'montant_ttc' => $reservation->montant_ttc,
            'suivi_url' => $resultat['jeton'] ? route('suivi', $resultat['jeton']) : null,
            'fiche_pdf_url' => URL::temporarySignedRoute('fiche.pdf', now()->addHours(2), ['numero' => $reservation->numero]),
        ], $resultat['deja_creee'] ? 200 : 201);
    }
}
