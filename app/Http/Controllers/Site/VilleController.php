<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Http\Requests\RechercheRequest;
use App\Models\TypeChambre;
use App\Models\Ville;
use App\Services\AvailabilityService;
use Illuminate\Contracts\View\View;

class VilleController extends Controller
{
    public function __invoke(Ville $ville, RechercheRequest $request, AvailabilityService $disponibilites): View
    {
        $ville->load(['sites' => fn ($q) => $q->where('actif', true)->withCount('chambres')->with('photos')]);

        $arrivee = $request->arrivee();
        $depart = $request->depart();
        $chambres = $disponibilites->chambresDisponibles($arrivee, $depart, $ville->id, null, $request->personnes(), $request->typeId());

        return view('pages.ville', [
            'ville' => $ville,
            'sites' => $ville->sites,
            'chambres' => $chambres,
            'arrivee' => $arrivee,
            'depart' => $depart,
            'personnes' => $request->personnes(),
            'typeId' => $request->typeId(),
            'types' => TypeChambre::query()->orderBy('capacite')->orderBy('libelle')->get(),
        ]);
    }
}
