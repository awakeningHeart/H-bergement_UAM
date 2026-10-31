<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Http\Requests\RechercheRequest;
use App\Models\TypeChambre;
use App\Models\Ville;
use App\Services\AvailabilityService;
use Illuminate\Contracts\View\View;

class HomeController extends Controller
{
    public function __invoke(RechercheRequest $request, AvailabilityService $disponibilites): View
    {
        $villes = Ville::query()
            ->whereHas('sites', fn ($q) => $q->where('actif', true))
            ->with(['sites' => fn ($q) => $q->where('actif', true)->withCount('chambres')->with('photos')])
            ->orderBy('nom')
            ->get();

        // Par défaut, la ville qui compte le plus de maisons d'hôtes (Niamey).
        $ville = $villes->firstWhere('slug', $request->validated('ville'))
            ?? $villes->sortByDesc(fn (Ville $v) => $v->sites->count())->first();
        $site = $request->validated('site')
            ? $ville?->sites->firstWhere('slug', $request->validated('site'))
            : null;

        $arrivee = $request->arrivee();
        $depart = $request->depart();

        $chambres = $ville
            ? $disponibilites->chambresDisponibles($arrivee, $depart, $ville->id, $site?->id, $request->personnes(), $request->typeId())
            : collect();

        return view('pages.accueil', [
            'villes' => $villes,
            'sites' => $villes->flatMap->sites,
            'ville' => $ville,
            'site' => $site,
            'arrivee' => $arrivee,
            'depart' => $depart,
            'personnes' => $request->personnes(),
            'typeId' => $request->typeId(),
            'types' => TypeChambre::query()->orderBy('capacite')->orderBy('libelle')->get(),
            'chambres' => $chambres->take(12),
            'totalChambres' => $chambres->count(),
        ]);
    }
}
