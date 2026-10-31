<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Http\Requests\RechercheRequest;
use App\Models\Site;
use App\Models\TypeChambre;
use App\Services\AvailabilityService;
use Illuminate\Contracts\View\View;

class SiteController extends Controller
{
    public function __invoke(Site $site, RechercheRequest $request, AvailabilityService $disponibilites): View
    {
        abort_unless($site->actif, 404);
        $site->load(['ville', 'photos']);

        $arrivee = $request->arrivee();
        $depart = $request->depart();
        $chambres = $disponibilites->chambresDisponibles($arrivee, $depart, null, $site->id, $request->personnes(), $request->typeId());

        return view('pages.site', [
            'site' => $site,
            'ville' => $site->ville,
            'chambres' => $chambres,
            'nbChambres' => $site->chambres()->where('actif', true)->count(),
            'arrivee' => $arrivee,
            'depart' => $depart,
            'personnes' => $request->personnes(),
            'typeId' => $request->typeId(),
            'types' => TypeChambre::query()->orderBy('capacite')->orderBy('libelle')->get(),
        ]);
    }
}
