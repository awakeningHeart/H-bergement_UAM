<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Http\Requests\RechercheRequest;
use App\Models\Chambre;
use App\Services\AvailabilityService;
use App\Services\PricingService;
use Illuminate\Contracts\View\View;

class ChambreController extends Controller
{
    public function __invoke(Chambre $chambre, RechercheRequest $request, AvailabilityService $disponibilites, PricingService $tarification): View
    {
        $chambre->load(['site.ville', 'type', 'photos']);
        abort_unless($chambre->actif && $chambre->site->actif, 404);

        $arrivee = $request->arrivee();
        $depart = $request->depart();
        $tarif = $chambre->tarifA($arrivee);

        return view('pages.chambre', [
            'chambre' => $chambre,
            'site' => $chambre->site,
            'ville' => $chambre->site->ville,
            'arrivee' => $arrivee,
            'depart' => $depart,
            'disponible' => $disponibilites->estDisponible($chambre, $arrivee, $depart),
            'tarif' => $tarif,
            'nuitees' => $tarification->nuitees($arrivee->setTime(14, 0), $depart->setTime(10, 0)),
        ]);
    }
}
