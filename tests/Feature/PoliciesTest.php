<?php

use App\Models\Chambre;
use App\Models\Facture;
use App\Models\Reservation;
use App\Models\Site;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->siteA = Site::factory()->create();
    $this->siteB = Site::factory()->create();
    $this->chambreA = Chambre::factory()->create(['site_id' => $this->siteA->id]);
    $this->chambreB = Chambre::factory()->create(['site_id' => $this->siteB->id]);
    $this->resA = Reservation::factory()->create(['chambre_id' => $this->chambreA->id]);
    $this->resB = Reservation::factory()->create(['chambre_id' => $this->chambreB->id]);

    $this->admin = User::factory()->create()->assignRole(User::ROLE_ADMIN);
    $this->gestionnaire = User::factory()->create()->assignRole(User::ROLE_GESTIONNAIRE);
    $this->gestionnaire->sites()->attach($this->siteA);
    $this->direction = User::factory()->create()->assignRole(User::ROLE_DIRECTION);
});

it('limite un gestionnaire à ses sites (RG11)', function () {
    expect($this->gestionnaire->can('view', $this->resA))->toBeTrue()
        ->and($this->gestionnaire->can('update', $this->resA))->toBeTrue()
        ->and($this->gestionnaire->can('view', $this->resB))->toBeFalse()
        ->and($this->gestionnaire->can('update', $this->resB))->toBeFalse()
        ->and($this->gestionnaire->can('view', $this->siteB))->toBeFalse()
        ->and($this->gestionnaire->can('view', $this->chambreB))->toBeFalse();
});

it('filtre les requêtes par site accessible (RG11)', function () {
    expect(Reservation::accessiblesPar($this->gestionnaire)->pluck('id')->all())->toBe([$this->resA->id])
        ->and(Chambre::accessiblesPar($this->gestionnaire)->pluck('id')->all())->toBe([$this->chambreA->id])
        ->and(Site::accessiblesPar($this->gestionnaire)->pluck('id')->all())->toBe([$this->siteA->id])
        ->and(Reservation::accessiblesPar($this->admin)->count())->toBe(2)
        ->and(Reservation::accessiblesPar($this->direction)->count())->toBe(2);
});

it('laisse l\'administrateur tout gérer', function () {
    expect($this->admin->can('update', $this->resB))->toBeTrue()
        ->and($this->admin->can('create', Site::class))->toBeTrue()
        ->and($this->admin->can('viewAny', User::class))->toBeTrue();
});

it('met la direction en lecture seule', function () {
    expect($this->direction->can('view', $this->resA))->toBeTrue()
        ->and($this->direction->can('update', $this->resA))->toBeFalse()
        ->and($this->direction->can('create', Site::class))->toBeFalse()
        ->and($this->direction->can('viewAny', User::class))->toBeFalse();
});

it('réserve les référentiels et utilisateurs à l\'administrateur', function () {
    expect($this->gestionnaire->can('create', Site::class))->toBeFalse()
        ->and($this->gestionnaire->can('viewAny', User::class))->toBeFalse()
        ->and($this->gestionnaire->can('viewAny', Site::class))->toBeTrue();
});

it('interdit toute modification de facture, même à l\'administrateur (RG12)', function () {
    $facture = Facture::factory()->create(['reservation_id' => $this->resA->id]);

    expect($this->admin->can('update', $facture))->toBeFalse()
        ->and($this->admin->can('delete', $facture))->toBeFalse()
        ->and($this->gestionnaire->can('view', $facture))->toBeTrue();
});

it('refuse l\'accès au back-office aux comptes inactifs ou sans rôle', function () {
    $panel = Filament\Facades\Filament::getPanel('admin');
    $inactif = User::factory()->create(['actif' => false])->assignRole(User::ROLE_ADMIN);
    $sansRole = User::factory()->create();

    expect($this->admin->canAccessPanel($panel))->toBeTrue()
        ->and($inactif->canAccessPanel($panel))->toBeFalse()
        ->and($sansRole->canAccessPanel($panel))->toBeFalse();
});
