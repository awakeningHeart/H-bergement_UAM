<?php

use App\Enums\ModePaiement;
use App\Enums\Payeur;
use App\Enums\StatutReservation;
use App\Enums\TypeFacture;
use App\Exceptions\ReservationException;
use App\Models\Chambre;
use App\Models\Client;
use App\Models\Facture;
use App\Models\Organisme;
use App\Models\Paiement;
use App\Models\Reservation;
use App\Models\Site;
use App\Models\User;
use App\Services\FactureService;
use App\Services\PaiementService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    Storage::fake('local');
    $this->admin = User::factory()->create()->assignRole(User::ROLE_ADMIN);
    $this->chambre = Chambre::factory()->create();
    $this->resa = Reservation::factory()->statut(StatutReservation::EnCours)->create([
        'chambre_id' => $this->chambre->id, 'montant_ht' => 100000, 'montant_tva' => 19000, 'montant_ttc' => 119000, 'nuitees' => 4,
        'cles_remises_le' => now(),
    ]);
    $this->factures = app(FactureService::class);
    $this->paiements = app(PaiementService::class);
});

// --- Factures et avoirs (RG12) -------------------------------------------------------------

it('émet une facture numérotée FAC-AAAA-NNNNN avec PDF figé', function () {
    $facture = $this->factures->emettre($this->resa, $this->admin);

    expect($facture->numero)->toBe('FAC-'.now('Africa/Niamey')->year.'-00001')
        ->and($facture->type)->toBe(TypeFacture::Facture)
        ->and($facture->montant_ttc)->toBe(119000)
        ->and($facture->pdf_path)->toBe('factures/'.$facture->numero.'.pdf');
    Storage::disk('local')->assertExists($facture->pdf_path);
    expect(substr(Storage::disk('local')->get($facture->pdf_path), 0, 4))->toBe('%PDF');
});

it('refuse une seconde facture tant que la première n\'est pas annulée par un avoir', function () {
    $this->factures->emettre($this->resa);

    expect(fn () => $this->factures->emettre($this->resa))->toThrow(ReservationException::class, 'avoir');
});

it('rend la facture immuable, même pour l\'administrateur (RG12)', function () {
    $facture = $this->factures->emettre($this->resa);
    $facture = Facture::find($facture->id);

    expect(fn () => $facture->update(['montant_ttc' => 1]))->toThrow(LogicException::class)
        ->and(fn () => $facture->update(['donnees' => ['x' => 1]]))->toThrow(LogicException::class)
        ->and(fn () => $facture->delete())->toThrow(LogicException::class)
        ->and(fn () => Facture::whereKey($facture->id)->first()->forceDelete())->toThrow(LogicException::class)
        ->and($this->admin->can('update', $facture))->toBeFalse()
        ->and($this->admin->can('delete', $facture))->toBeFalse()
        ->and($facture->fresh()->montant_ttc)->toBe(119000);
});

it('corrige par un avoir négatif numéroté AV-AAAA-NNNNN en conservant la facture', function () {
    $facture = $this->factures->emettre($this->resa);
    $avoir = $this->factures->emettreAvoir($facture, 'Erreur de saisie', $this->admin);

    expect($avoir->numero)->toBe('AV-'.now('Africa/Niamey')->year.'-00001')
        ->and($avoir->type)->toBe(TypeFacture::Avoir)
        ->and($avoir->facture_origine_id)->toBe($facture->id)
        ->and($avoir->montant_ht)->toBe(-100000)
        ->and($avoir->montant_tva)->toBe(-19000)
        ->and($avoir->montant_ttc)->toBe(-119000)
        ->and($avoir->motif_avoir)->toBe('Erreur de saisie')
        ->and(Facture::find($facture->id)->montant_ttc)->toBe(119000)
        ->and($this->resa->fresh()->soldeDu())->toBe(0);
    Storage::disk('local')->assertExists($avoir->pdf_path);
});

it('refuse un second avoir, un avoir sur un avoir, et autorise une nouvelle facture après avoir', function () {
    $facture = $this->factures->emettre($this->resa);
    $avoir = $this->factures->emettreAvoir($facture, 'Correction');

    expect(fn () => $this->factures->emettreAvoir($facture, 'Encore'))->toThrow(ReservationException::class)
        ->and(fn () => $this->factures->emettreAvoir($avoir, 'Avoir d\'avoir'))->toThrow(ReservationException::class);

    $nouvelle = $this->factures->emettre($this->resa);
    expect($nouvelle->numero)->toEndWith('-00002')->and($this->resa->fresh()->soldeDu())->toBe(119000);
});

it('numérote factures et avoirs dans des séquences distinctes sans trou', function () {
    $f1 = $this->factures->emettre($this->resa);
    $deuxieme = Reservation::factory()->statut(StatutReservation::EnCours)->create(['chambre_id' => Chambre::factory()->create()->id]);
    $f2 = $this->factures->emettre($deuxieme);
    $a1 = $this->factures->emettreAvoir($f1, 'x');

    expect([substr($f1->numero, -5), substr($f2->numero, -5), substr($a1->numero, -5)])->toBe(['00001', '00002', '00001']);
});

it('fige le contenu : un changement ultérieur du client ne modifie pas la facture', function () {
    $facture = $this->factures->emettre($this->resa);
    $this->resa->client->update(['nom' => 'NouveauNom']);

    expect(Facture::find($facture->id)->donnees['client']['nom'])->not->toContain('NouveauNom');
    expect(app(FactureService::class)->contenuPdf(Facture::find($facture->id)))->toBe(Storage::disk('local')->get($facture->pdf_path));
});

it('facture au nom de l\'organisme lorsqu\'il prend en charge (RG08), sans délai de paiement', function () {
    $organisme = Organisme::factory()->create(['nom' => 'Ministère partenaire']);
    $resa = Reservation::factory()->statut(StatutReservation::EnCours)->create([
        'chambre_id' => Chambre::factory()->create()->id, 'payeur' => Payeur::Organisme, 'organisme_id' => $organisme->id, 'cles_remises_le' => now(),
    ]);

    $facture = $this->factures->emettre($resa);

    expect($facture->donnees['facture_a']['nom'])->toBe('Ministère partenaire')
        ->and($facture->donnees['facture_a']['organisme'])->toBeTrue()
        ->and($facture->donnees['delai_paiement_heures'])->toBeNull()
        ->and($resa->echeancePaiement())->toBeNull();
});

it('refuse l\'émission à un utilisateur sans la permission factures.gerer', function () {
    $direction = User::factory()->create()->assignRole(User::ROLE_DIRECTION);

    expect(fn () => $this->factures->emettre($this->resa, $direction))->toThrow(ReservationException::class);
});

it('sert la facture au client par lien signé et au personnel authentifié, selon RG11', function () {
    $facture = $this->factures->emettre($this->resa);

    $this->get(route('facture.pdf', $facture->numero))->assertForbidden();
    $this->get(URL::temporarySignedRoute('facture.pdf', now()->addMinutes(5), ['numero' => $facture->numero]))
        ->assertOk()->assertHeader('content-type', 'application/pdf');

    $this->get(route('admin.facture.pdf', $facture))->assertRedirect(route('filament.admin.auth.login'));
    $this->actingAs($this->admin)->get(route('admin.facture.pdf', $facture))->assertOk();

    $gestionnaire = User::factory()->create()->assignRole(User::ROLE_GESTIONNAIRE);
    $gestionnaire->sites()->attach(Site::factory()->create());
    $this->actingAs($gestionnaire)->get(route('admin.facture.pdf', $facture))->assertForbidden();
});

// --- Paiements -----------------------------------------------------------------------------

it('enregistre un paiement et met à jour le solde', function () {
    $this->paiements->enregistrer($this->resa, 50000, ModePaiement::MobileMoney, 'MM-123', $this->admin);

    $paiement = Paiement::firstOrFail();
    expect($paiement->montant)->toBe(50000)
        ->and($paiement->mode)->toBe(ModePaiement::MobileMoney)
        ->and($paiement->reference)->toBe('MM-123')
        ->and($paiement->recu_par)->toBe($this->admin->id)
        ->and($this->resa->fresh()->soldeDu())->toBe(69000);

    $this->paiements->enregistrer($this->resa, 69000, ModePaiement::Especes, null, $this->admin);
    expect($this->resa->fresh()->soldeDu())->toBe(0);
});

it('refuse un paiement nul, supérieur au solde ou sur un statut inadapté', function () {
    expect(fn () => $this->paiements->enregistrer($this->resa, 0, ModePaiement::Especes, null, $this->admin))->toThrow(ReservationException::class)
        ->and(fn () => $this->paiements->enregistrer($this->resa, 119001, ModePaiement::Especes, null, $this->admin))->toThrow(ReservationException::class);

    $attente = Reservation::factory()->create(['chambre_id' => $this->chambre->id]);
    expect(fn () => $this->paiements->enregistrer($attente, 1000, ModePaiement::Especes, null, $this->admin))->toThrow(ReservationException::class);
    expect(Paiement::count())->toBe(0);
});

it('limite les paiements aux utilisateurs autorisés sur le site (RG11)', function () {
    $direction = User::factory()->create()->assignRole(User::ROLE_DIRECTION);
    $gestionnaire = User::factory()->create()->assignRole(User::ROLE_GESTIONNAIRE);
    $gestionnaire->sites()->attach(Site::factory()->create());

    expect(fn () => $this->paiements->enregistrer($this->resa, 1000, ModePaiement::Especes, null, $direction))->toThrow(ReservationException::class)
        ->and(fn () => $this->paiements->enregistrer($this->resa, 1000, ModePaiement::Especes, null, $gestionnaire))->toThrow(ReservationException::class);

    $gestionnaire->sites()->attach($this->chambre->site_id);
    $this->paiements->enregistrer($this->resa, 1000, ModePaiement::Especes, null, $gestionnaire);
    expect(Paiement::count())->toBe(1);
});

it('calcule l\'échéance à 48 h après la remise des clés (RG07)', function () {
    $echeance = $this->resa->fresh()->echeancePaiement();

    expect($echeance->diffInHours($this->resa->cles_remises_le, true))->toBe(48.0);

    $this->resa->update(['cles_remises_le' => null]);
    expect($this->resa->fresh()->echeancePaiement())->toBeNull();
});

it('calcule le solde sans facture selon le statut', function () {
    $client = Client::factory()->create();
    $attente = Reservation::factory()->create(['client_id' => $client->id, 'chambre_id' => $this->chambre->id, 'montant_ttc' => 50000]);
    $confirmee = Reservation::factory()->statut(StatutReservation::Confirmee)->create(['chambre_id' => Chambre::factory()->create()->id, 'montant_ttc' => 50000]);
    $annulee = Reservation::factory()->statut(StatutReservation::Annulee)->create(['chambre_id' => Chambre::factory()->create()->id, 'montant_ttc' => 50000]);

    expect($attente->soldeDu())->toBe(0)->and($confirmee->soldeDu())->toBe(50000)->and($annulee->soldeDu())->toBe(0);
});
