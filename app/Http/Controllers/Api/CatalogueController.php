<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\RechercheRequest;
use App\Models\Chambre;
use App\Models\Site;
use App\Models\Ville;
use App\Services\AvailabilityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** API publique du catalogue : aucune donnée personnelle. */
class CatalogueController extends Controller
{
    /** GET /api/sites?ville= */
    public function sites(Request $request): JsonResponse
    {
        $request->validate(['ville' => ['nullable', 'string', 'max:100', 'exists:villes,slug']]);

        $ville = $request->query('ville') ? Ville::query()->where('slug', $request->query('ville'))->first() : null;

        $sites = Site::query()
            ->where('actif', true)
            ->when($ville, fn ($q) => $q->where('ville_id', $ville->id))
            ->withCount(['chambres' => fn ($q) => $q->where('actif', true)])
            ->orderBy('nom')
            ->get(['id', 'ville_id', 'nom', 'slug', 'adresse', 'latitude', 'longitude']);

        return response()->json(['data' => $sites->map(fn ($s) => [
            'nom' => $s->nom,
            'slug' => $s->slug,
            'adresse' => $s->adresse,
            'latitude' => $s->latitude !== null ? (float) $s->latitude : null,
            'longitude' => $s->longitude !== null ? (float) $s->longitude : null,
            'chambres' => $s->chambres_count,
        ])]);
    }

    /** GET /api/chambres/disponibles?site=&arrivee=&depart= */
    public function disponibles(RechercheRequest $request, AvailabilityService $disponibilites): JsonResponse
    {
        $site = $request->validated('site')
            ? Site::query()->where('slug', $request->validated('site'))->where('actif', true)->first()
            : null;
        $ville = $request->validated('ville')
            ? Ville::query()->where('slug', $request->validated('ville'))->first()
            : null;

        $chambres = $disponibilites->chambresDisponibles(
            $request->arrivee(), $request->depart(), $ville?->id, $site?->id, $request->personnes(), $request->typeId(),
        );

        return response()->json(['data' => $chambres->map(fn (Chambre $c) => [
            'id' => $c->id,
            'numero' => $c->numero,
            'site' => $c->site->nom,
            'ville' => $c->site->ville->nom,
            'type' => $c->type->libelle,
            'capacite' => $c->type->capacite,
            'equipements' => $c->type->equipements ?? [],
            'prix_nuit' => $c->getAttribute('tarif_en_vigueur')->prix_nuit,
            'url' => route('chambre', $c),
        ])->values()]);
    }
}
