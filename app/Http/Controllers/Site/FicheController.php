<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Parametre;
use App\Models\Reservation;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

/** Fiche de réservation en PDF, avec QR code (accès par lien signé uniquement). */
class FicheController extends Controller
{
    public function pdf(string $numero): Response
    {
        $reservation = Reservation::query()
            ->with(['client', 'chambre.site.ville', 'chambre.type', 'organisme', 'repas'])
            ->where('numero', $numero)
            ->firstOrFail();

        $qr = QrCode::format('svg')->size(150)->margin(0)->generate(route('admin.scan', $reservation->tokenScan()));

        $pdf = Pdf::loadView('pdf.fiche', [
            'reservation' => $reservation,
            'qr' => 'data:image/svg+xml;base64,'.base64_encode((string) $qr),
            'logo' => 'data:image/png;base64,'.base64_encode((string) file_get_contents(public_path('images/logo-hebergement-uam.png'))),
            'conditions' => Parametre::valeur('conditions_generales'),
        ])->setPaper('a4');

        return $pdf->stream('fiche-'.$reservation->numero.'.pdf');
    }
}
