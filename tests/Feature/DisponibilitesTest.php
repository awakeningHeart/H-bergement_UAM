<?php

use App\Enums\StatutReservation;
use App\Models\Chambre;
use App\Models\ChambreHold;
use App\Models\Parametre;
use App\Models\Reservation;
use App\Models\Tarif;
use App\Models\TypeChambre;
use App\Services\AvailabilityService;
use Carbon\CarbonImmutable;

beforeEach(function () {
    $this->service = app(AvailabilityService::class);
    $this->chambre = Chambre::factory()->create();
    Tarif::factory()->create(['type_chambre_id' => $this->chambre->type_chambre_id, 'prix_nuit' => 18000]);
});

/** Réservation avec heures locales de Niamey. */
function reserver(Chambre $chambre, string $arrivee, string $depart, StatutReservation $statut = StatutReservation::Confirmee): Reservation
{
    return Reservation::factory()->statut($statut)->create([
        'chambre_id' => $chambre->id,
        'arrivee' => CarbonImmutable::parse($arrivee, 'Africa/Niamey')->utc(),
        'depart' => CarbonImmutable::parse($depart, 'Africa/Niamey')->utc(),
    ]);
}

it('exclut une chambre dont la réservation chevauche la période (RG02)', function () {
    reserver($this->chambre, '2026-12-10 14:00', '2026-12-12 10:00');

    expect($this->service->estDisponible($this->chambre, '2026-12-11', '2026-12-13'))->toBeFalse()
        ->and($this->service->estDisponible($this->chambre, '2026-12-09', '2026-12-11'))->toBeFalse()
        ->and($this->service->estDisponible($this->chambre, '2026-12-10', '2026-12-12'))->toBeFalse()
        ->and($this->service->estDisponible($this->chambre, '2026-12-09', '2026-12-14'))->toBeFalse();
});

it('autorise l\'enchaînement départ le matin / arrivée l\'après-midi le même jour', function () {
    reserver($this->chambre, '2026-12-10 14:00', '2026-12-12 10:00');

    expect($this->service->estDisponible($this->chambre, '2026-12-12', '2026-12-14'))->toBeTrue()
        ->and($this->service->estDisponible($this->chambre, '2026-12-08', '2026-12-10'))->toBeTrue();
});

it('bloque le jour du départ quand le départ dépasse l\'heure limite (RG05)', function () {
    reserver($this->chambre, '2026-12-10 14:00', '2026-12-12 16:00');

    expect($this->service->estDisponible($this->chambre, '2026-12-12', '2026-12-13'))->toBeFalse();
});

it('ignore les réservations refusées, expirées, annulées, terminées ou non présentées', function (StatutReservation $statut) {
    reserver($this->chambre, '2026-12-10 14:00', '2026-12-12 10:00', $statut);

    expect($this->service->estDisponible($this->chambre, '2026-12-10', '2026-12-12'))->toBeTrue();
})->with([
    StatutReservation::Refusee, StatutReservation::Expiree, StatutReservation::Annulee,
    StatutReservation::Terminee, StatutReservation::NonPresente,
]);

it('compte les demandes en attente, confirmées et en cours comme occupées', function (StatutReservation $statut) {
    reserver($this->chambre, '2026-12-10 14:00', '2026-12-12 10:00', $statut);

    expect($this->service->estDisponible($this->chambre, '2026-12-10', '2026-12-12'))->toBeFalse();
})->with([StatutReservation::EnAttente, StatutReservation::Confirmee, StatutReservation::EnCours]);

it('compte les holds actifs et ignore les holds expirés (RG13)', function () {
    ChambreHold::factory()->create([
        'chambre_id' => $this->chambre->id, 'arrivee' => '2026-12-10', 'depart' => '2026-12-12', 'expire_le' => now()->addMinutes(10),
    ]);

    expect($this->service->estDisponible($this->chambre, '2026-12-11', '2026-12-13'))->toBeFalse()
        ->and($this->service->estDisponible($this->chambre, '2026-12-12', '2026-12-14'))->toBeTrue();

    ChambreHold::query()->update(['expire_le' => now()->subMinute()]);

    expect($this->service->estDisponible($this->chambre, '2026-12-11', '2026-12-13'))->toBeTrue();
});

it('laisse le porteur d\'un hold revérifier sa propre chambre', function () {
    $hold = ChambreHold::factory()->create([
        'chambre_id' => $this->chambre->id, 'arrivee' => '2026-12-10', 'depart' => '2026-12-12', 'expire_le' => now()->addMinutes(10),
    ]);

    expect($this->service->estDisponible($this->chambre, '2026-12-10', '2026-12-12'))->toBeFalse()
        ->and($this->service->estDisponible($this->chambre, '2026-12-10', '2026-12-12', $hold->token))->toBeTrue();
});

it('n\'étend pas l\'indisponibilité aux autres chambres', function () {
    $autre = Chambre::factory()->create(['site_id' => $this->chambre->site_id]);
    reserver($this->chambre, '2026-12-10 14:00', '2026-12-12 10:00');

    expect($this->service->estDisponible($autre, '2026-12-10', '2026-12-12'))->toBeTrue();
});

it('liste les chambres libres avec leur tarif et filtre par site, capacité et type', function () {
    $site = $this->chambre->site;
    $grande = Chambre::factory()->create([
        'site_id' => $site->id,
        'type_chambre_id' => TypeChambre::factory()->create(['capacite' => 3])->id,
    ]);
    Tarif::factory()->create(['type_chambre_id' => $grande->type_chambre_id, 'prix_nuit' => 40000]);
    $this->chambre->type->update(['capacite' => 1]);
    $ailleurs = Chambre::factory()->create();
    Tarif::factory()->create(['type_chambre_id' => $ailleurs->type_chambre_id]);
    reserver($grande, '2026-12-10 14:00', '2026-12-12 10:00');

    $tous = $this->service->chambresDisponibles('2026-12-10', '2026-12-12', siteId: $site->id);
    expect($tous->pluck('id')->all())->toBe([$this->chambre->id])
        ->and($tous->first()->tarif_en_vigueur->prix_nuit)->toBe(18000);

    expect($this->service->chambresDisponibles('2026-12-20', '2026-12-22', siteId: $site->id)->pluck('id')->sort()->values()->all())
        ->toBe(collect([$this->chambre->id, $grande->id])->sort()->values()->all())
        ->and($this->service->chambresDisponibles('2026-12-20', '2026-12-22', siteId: $site->id, personnes: 2)->pluck('id')->all())->toBe([$grande->id])
        ->and($this->service->chambresDisponibles('2026-12-20', '2026-12-22', siteId: $site->id, typeId: $this->chambre->type_chambre_id)->pluck('id')->all())->toBe([$this->chambre->id])
        ->and($this->service->chambresDisponibles('2026-12-20', '2026-12-22', villeId: $ailleurs->site->ville_id)->pluck('id')->all())->toBe([$ailleurs->id]);
});

it('n\'expose ni chambre inactive, ni site inactif, ni chambre sans tarif', function () {
    $site = $this->chambre->site;
    $inactive = Chambre::factory()->create(['site_id' => $site->id, 'actif' => false]);
    $sansTarif = Chambre::factory()->create(['site_id' => $site->id]);
    Tarif::factory()->create(['type_chambre_id' => $inactive->type_chambre_id]);

    expect($this->service->chambresDisponibles('2026-12-10', '2026-12-12', siteId: $site->id)->pluck('id')->all())->toBe([$this->chambre->id]);

    $site->update(['actif' => false]);
    expect($this->service->chambresDisponibles('2026-12-10', '2026-12-12', siteId: $site->id))->toBeEmpty()
        ->and($sansTarif->id)->not->toBeNull();
});

it('lit l\'heure de départ dans les paramètres', function () {
    Parametre::create(['cle' => 'heure_depart', 'valeur' => 11]);
    reserver($this->chambre, '2026-12-10 14:00', '2026-12-12 11:30');

    // Départ à 11:30 > 11:00 : le jour de départ reste bloqué.
    expect($this->service->estDisponible($this->chambre, '2026-12-12', '2026-12-13'))->toBeFalse();
});
