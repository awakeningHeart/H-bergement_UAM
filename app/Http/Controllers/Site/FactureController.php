<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Facture;
use App\Services\FactureService;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class FactureController extends Controller
{
    /** Client : lien signé temporaire (suivi). */
    public function client(string $numero, FactureService $factures): Response
    {
        return $this->pdf(Facture::query()->where('numero', $numero)->firstOrFail(), $factures);
    }

    /** Personnel : réservé aux utilisateurs autorisés sur le site de la réservation (RG11). */
    public function personnel(Facture $facture, FactureService $factures): Response
    {
        Gate::authorize('view', $facture);

        return $this->pdf($facture, $factures);
    }

    private function pdf(Facture $facture, FactureService $factures): Response
    {
        return response($factures->contenuPdf($facture), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$facture->numero.'.pdf"',
        ]);
    }
}
