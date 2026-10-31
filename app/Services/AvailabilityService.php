<?php

namespace App\Services;

use App\Enums\StatutReservation;
use App\Models\Chambre;
use App\Models\Parametre;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

/**
 * Disponibilité des chambres (RG02) : une chambre est occupée sur [arrivée, départ[
 * par toute réservation active ou tout hold non expiré qui chevauche la période.
 */
class AvailabilityService
{
    /** Heure de départ (RG05) : paramètre `heure_depart`, sinon configuration. */
    public function heureDepart(): int
    {
        return (int) Parametre::valeur('heure_depart', config('uam.checkout_hour'));
    }

    /**
     * Bornes UTC d'un séjour. Les dates sont normalisées à l'heure de départ (12:00 à Niamey) :
     * un départ le matin du jour J ne bloque donc pas une arrivée l'après-midi de J.
     *
     * @return array{0: CarbonImmutable, 1: CarbonImmutable}
     */
    public function bornes(CarbonInterface|string $arrivee, CarbonInterface|string $depart): array
    {
        $tz = config('uam.timezone');
        $heure = $this->heureDepart();
        $debut = CarbonImmutable::parse($arrivee instanceof CarbonInterface ? $arrivee->toDateString() : $arrivee, $tz)->setTime($heure, 0);
        $fin = CarbonImmutable::parse($depart instanceof CarbonInterface ? $depart->toDateString() : $depart, $tz)->setTime($heure, 0);

        return [$debut->utc(), $fin->utc()];
    }

    /** @return Builder<Chambre> */
    public function requeteLibres(CarbonInterface|string $arrivee, CarbonInterface|string $depart, ?string $tokenIgnore = null): Builder
    {
        [$debut, $fin] = $this->bornes($arrivee, $depart);
        $dateArrivee = CarbonImmutable::parse($arrivee instanceof CarbonInterface ? $arrivee->toDateString() : $arrivee)->toDateString();
        $dateDepart = CarbonImmutable::parse($depart instanceof CarbonInterface ? $depart->toDateString() : $depart)->toDateString();
        $statutsActifs = array_map(fn (StatutReservation $s) => $s->value, StatutReservation::actifs());

        return Chambre::query()
            ->where('chambres.actif', true)
            ->whereHas('site', fn (Builder $q) => $q->where('actif', true))
            ->whereDoesntHave('reservations', fn (Builder $q) => $q
                ->whereIn('statut', $statutsActifs)
                ->where('arrivee', '<', $fin)
                ->where('depart', '>', $debut))
            ->whereDoesntHave('holds', fn (Builder $q) => $q
                ->where('expire_le', '>', now())
                ->where('arrivee', '<', $dateDepart)
                ->where('depart', '>', $dateArrivee)
                ->when($tokenIgnore, fn (Builder $q) => $q->where('token', '!=', $tokenIgnore)));
    }

    public function estDisponible(Chambre $chambre, CarbonInterface|string $arrivee, CarbonInterface|string $depart, ?string $tokenIgnore = null): bool
    {
        return $this->requeteLibres($arrivee, $depart, $tokenIgnore)->whereKey($chambre->getKey())->exists();
    }

    /**
     * Chambres libres sur la période, avec leur tarif en vigueur à l'arrivée.
     * Les chambres sans tarif ne sont pas proposées (elles ne peuvent pas être facturées).
     *
     * @return Collection<int, Chambre>
     */
    public function chambresDisponibles(
        CarbonInterface|string $arrivee,
        CarbonInterface|string $depart,
        ?int $villeId = null,
        ?int $siteId = null,
        ?int $personnes = null,
        ?int $typeId = null,
    ): Collection {
        $dateArrivee = CarbonImmutable::parse($arrivee instanceof CarbonInterface ? $arrivee->toDateString() : $arrivee, config('uam.timezone'));

        $chambres = $this->requeteLibres($arrivee, $depart)
            ->with(['site.ville', 'type', 'photos'])
            ->when($siteId, fn (Builder $q) => $q->where('chambres.site_id', $siteId))
            ->when($villeId, fn (Builder $q) => $q->whereHas('site', fn (Builder $s) => $s->where('ville_id', $villeId)))
            ->when($typeId, fn (Builder $q) => $q->where('chambres.type_chambre_id', $typeId))
            ->when($personnes, fn (Builder $q) => $q->whereHas('type', fn (Builder $t) => $t->where('capacite', '>=', $personnes)))
            ->orderBy('chambres.site_id')
            ->orderBy('chambres.numero')
            ->get();

        return $chambres
            ->each(fn (Chambre $c) => $c->setAttribute('tarif_en_vigueur', $c->tarifA($dateArrivee)))
            ->filter(fn (Chambre $c) => $c->getAttribute('tarif_en_vigueur') !== null)
            ->values();
    }
}
