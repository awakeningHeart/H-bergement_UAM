<?php

use App\Enums\Repas;
use App\Models\Chambre;
use App\Models\Parametre;
use App\Models\Tarif;
use App\Models\TarifRepas;
use App\Services\PricingService;
use App\Support\Format;
use Carbon\CarbonImmutable;

beforeEach(function () {
    $this->service = app(PricingService::class);
    $this->chambre = Chambre::factory()->create();
    Tarif::factory()->create(['type_chambre_id' => $this->chambre->type_chambre_id, 'prix_nuit' => 25000, 'date_debut' => '2026-01-01']);
});

function local(string $date): CarbonImmutable
{
    return CarbonImmutable::parse($date, 'Africa/Niamey');
}

it('calcule les nuitées (RG05)', function () {
    expect($this->service->nuitees(local('2026-12-10 14:00'), local('2026-12-13 10:00')))->toBe(3)
        ->and($this->service->nuitees(local('2026-12-10 14:00'), local('2026-12-13 12:00')))->toBe(3)
        ->and($this->service->nuitees(local('2026-12-10 14:00'), local('2026-12-13 12:01')))->toBe(4)
        ->and($this->service->nuitees(local('2026-12-10 14:00'), local('2026-12-10 18:00')))->toBe(1)
        ->and($this->service->nuitees(local('2026-12-13 14:00'), local('2026-12-10 10:00')))->toBe(0);
});

it('lit la même heure limite quel que soit le fuseau d\'entrée', function () {
    // 11:30 UTC = 12:30 à Niamey : après l'heure limite.
    $depart = CarbonImmutable::parse('2026-12-13 11:30', 'UTC');

    expect($this->service->nuitees(local('2026-12-10 14:00'), $depart))->toBe(4);
});

it('calcule HT, TVA arrondie à l\'entier supérieur et TTC (RG06)', function () {
    Parametre::create(['cle' => 'tva', 'valeur' => 19]);
    $devis = $this->service->devis($this->chambre, local('2026-12-10 14:00'), local('2026-12-13 10:00'));

    // 3 × 25 000 = 75 000 ; TVA 19 % = 14 250.
    expect($devis['nuitees'])->toBe(3)
        ->and($devis['montant_ht'])->toBe(75000)
        ->and($devis['montant_tva'])->toBe(14250)
        ->and($devis['montant_ttc'])->toBe(89250);

    Tarif::query()->update(['prix_nuit' => 18333]);
    $devis = $this->service->devis($this->chambre, local('2026-12-10 14:00'), local('2026-12-11 10:00'));
    // 18 333 × 19 % = 3 483,27 → 3 484.
    expect($devis['montant_tva'])->toBe(3484);
});

it('applique par défaut le taux de configuration (0 %)', function () {
    $devis = $this->service->devis($this->chambre, local('2026-12-10 14:00'), local('2026-12-12 10:00'));

    expect($devis['montant_tva'])->toBe(0)
        ->and($devis['montant_ttc'])->toBe(50000);
});

it('ajoute les repas au HT', function () {
    TarifRepas::create(['repas' => Repas::PetitDejeuner, 'prix' => 2500]);
    TarifRepas::create(['repas' => Repas::Diner, 'prix' => 5000]);

    $devis = $this->service->devis($this->chambre, local('2026-12-10 14:00'), local('2026-12-12 10:00'), [
        'petit_dejeuner' => 2, 'diner' => 2, 'dejeuner' => 0,
    ]);

    // 2 × 25 000 + 2 × 2 500 + 2 × 5 000 = 65 000.
    expect($devis['montant_ht'])->toBe(65000)
        ->and($devis['lignes'])->toHaveCount(3);
});

it('retient le tarif en vigueur à la date d\'arrivée', function () {
    Tarif::factory()->create(['type_chambre_id' => $this->chambre->type_chambre_id, 'prix_nuit' => 30000, 'date_debut' => '2026-12-01']);

    expect($this->service->devis($this->chambre, local('2026-11-29 14:00'), local('2026-12-02 10:00'))['prix_nuit'])->toBe(25000)
        ->and($this->service->devis($this->chambre, local('2026-12-01 14:00'), local('2026-12-03 10:00'))['prix_nuit'])->toBe(30000);
});

it('refuse un séjour sans nuitée ou sans tarif', function () {
    expect(fn () => $this->service->devis($this->chambre, local('2026-12-10 14:00'), local('2026-12-09 10:00')))
        ->toThrow(InvalidArgumentException::class);

    $sansTarif = Chambre::factory()->create();
    expect(fn () => $this->service->devis($sansTarif, local('2026-12-10 14:00'), local('2026-12-12 10:00')))
        ->toThrow(InvalidArgumentException::class);
});

it('formate les montants en FCFA avec espace insécable', function () {
    expect(Format::fcfa(25000))->toBe("25\u{202F}000\u{00A0}FCFA")
        ->and(Format::nombre(1250000))->toBe("1\u{202F}250\u{202F}000");
});
