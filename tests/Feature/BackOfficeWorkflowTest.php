<?php

use App\Enums\StatutReservation;
use App\Exports\ReservationsExport;
use App\Filament\Resources\Paiements\Pages\ListPaiements;
use App\Filament\Resources\Reservations\Pages\EditReservation;
use App\Filament\Resources\Reservations\Pages\ListReservations;
use App\Filament\Resources\Reservations\RelationManagers\FacturesRelationManager;
use App\Filament\Widgets\IndicateursReservations;
use App\Filament\Widgets\ReservationsParSite;
use App\Filament\Widgets\ReservationsParStatut;
use App\Filament\Widgets\RevenusMensuels;
use App\Models\Chambre;
use App\Models\Facture;
use App\Models\Paiement;
use App\Models\Reservation;
use App\Models\Site;
use App\Models\User;
use App\Services\FactureService;
use App\Services\Sms\SmsGateway;
use App\Services\StatistiquesService;
use Carbon\CarbonImmutable;
use Database\Seeders\RolesAndPermissionsSeeder;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Maatwebsite\Excel\Excel as ExcelType;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Spatie\Permission\PermissionRegistrar;
use Tests\Support\SmsCapture;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    Storage::fake('local');
    Filament::setCurrentPanel('admin');
    $this->sms = new SmsCapture;
    app()->instance(SmsGateway::class, $this->sms);
    $this->admin = User::factory()->create()->assignRole(User::ROLE_ADMIN);
    $this->chambre = Chambre::factory()->create();
    $this->resa = Reservation::factory()->create(['chambre_id' => $this->chambre->id, 'montant_ht' => 50000, 'montant_ttc' => 50000]);
});

function editer(Reservation $reservation)
{
    return Livewire::test(EditReservation::class, ['record' => $reservation->getRouteKey()]);
}

// --- Actions du workflow dans le back-office -----------------------------------------------

it('propose uniquement les actions autorisées par le statut', function () {
    $this->actingAs($this->admin);

    editer($this->resa)
        ->assertActionVisible('confirmer')->assertActionVisible('refuser')->assertActionVisible('annuler')
        ->assertActionHidden('arrivee')->assertActionHidden('depart')->assertActionHidden('non_presente')
        ->assertActionHidden('paiement')->assertActionHidden('facturer');
});

it('confirme, enregistre l\'arrivée, puis le départ depuis la fiche', function () {
    $this->actingAs($this->admin);

    editer($this->resa)->callAction('confirmer')->assertNotified();
    expect($this->resa->fresh()->statut)->toBe(StatutReservation::Confirmee);

    editer($this->resa)->assertActionVisible('arrivee')->callAction('arrivee')->assertNotified();
    $resa = $this->resa->fresh();
    expect($resa->statut)->toBe(StatutReservation::EnCours)
        ->and($resa->factures()->count())->toBe(1);

    editer($this->resa)->callAction('depart')->assertNotified();
    expect($this->resa->fresh()->statut)->toBe(StatutReservation::Terminee);
});

it('refuse une demande avec un motif transmis au client', function () {
    $this->actingAs($this->admin);

    editer($this->resa)->callAction('refuser', ['motif' => 'Site fermé pour travaux'])->assertNotified();

    expect($this->resa->fresh()->statut)->toBe(StatutReservation::Refusee)
        ->and($this->sms->dernier()['message'])->toContain('Site fermé pour travaux');
});

it('enregistre un paiement depuis la fiche et refuse un montant supérieur au solde', function () {
    $this->actingAs($this->admin);
    $this->resa->update(['statut' => StatutReservation::Confirmee]);

    editer($this->resa)->callAction('paiement', ['montant' => 60000, 'mode' => 'especes'])->assertNotified();
    expect(Paiement::count())->toBe(0);

    editer($this->resa)->callAction('paiement', ['montant' => 20000, 'mode' => 'virement', 'reference' => 'VIR-1'])->assertNotified();
    expect(Paiement::count())->toBe(1)->and($this->resa->fresh()->soldeDu())->toBe(30000);
});

it('masque toutes les actions de workflow à la direction (lecture seule)', function () {
    $direction = User::factory()->create()->assignRole(User::ROLE_DIRECTION);
    $this->actingAs($direction);

    $this->get("/admin/reservations/{$this->resa->id}/edit")->assertForbidden();
    $this->get("/admin/reservations/{$this->resa->id}")->assertOk()->assertDontSee('Confirmer')->assertDontSee('Refuser');
});

it('n\'ouvre pas la fiche d\'une réservation d\'un autre site à un gestionnaire (RG11)', function () {
    $gestionnaire = User::factory()->create()->assignRole(User::ROLE_GESTIONNAIRE);
    $gestionnaire->sites()->attach(Site::factory()->create());

    $this->actingAs($gestionnaire)->get("/admin/reservations/{$this->resa->id}/edit")->assertNotFound();
});

it('n\'autorise pas la création manuelle de réservation ni l\'édition d\'un paiement', function () {
    $this->actingAs($this->admin);

    expect($this->get('/admin/reservations/create')->getStatusCode())->toBeIn([403, 404])
        ->and($this->get('/admin/paiements/create')->getStatusCode())->toBeIn([403, 404]);
});

it('permet d\'émettre un avoir depuis la fiche et conserve la facture', function () {
    $this->actingAs($this->admin);
    $this->resa->update(['statut' => StatutReservation::EnCours]);
    $facture = app(FactureService::class)->emettre($this->resa);

    Livewire::test(FacturesRelationManager::class, ['ownerRecord' => $this->resa, 'pageClass' => EditReservation::class])
        ->assertCanSeeTableRecords([$facture])
        ->callTableAction('avoir', $facture, ['motif' => 'Erreur de tarif'])
        ->assertNotified();

    expect(Facture::count())->toBe(2)->and(Facture::find($facture->id)->montant_ttc)->toBe(50000);
});

// --- Tableau de bord -----------------------------------------------------------------------

it('affiche le tableau de bord avec ses widgets', function () {
    $this->actingAs($this->admin)->get('/admin')->assertOk();

    Livewire::test(IndicateursReservations::class)
        ->assertSee('Demandes en attente')
        ->assertSee("Taux d'occupation du mois")
        ->assertSee('Revenus encaissés')
        ->assertSee('Impayés');
    Livewire::test(ReservationsParStatut::class)->assertSee('Réservations par statut');
    Livewire::test(ReservationsParSite::class)->assertSee('Réservations par site');
    Livewire::test(RevenusMensuels::class)->assertSee('Revenus encaissés');
});

it('calcule le taux d\'occupation, les arrivées et les revenus, limités aux sites de l\'utilisateur', function () {
    $this->travelTo(CarbonImmutable::parse('2026-11-15 10:00', 'Africa/Niamey')->utc());
    $site = Site::factory()->create();
    $a = Chambre::factory()->create(['site_id' => $site->id]);
    Chambre::factory()->create(['site_id' => $site->id]);
    $autreSite = Site::factory()->create();
    $b = Chambre::factory()->create(['site_id' => $autreSite->id]);
    $tz = 'Africa/Niamey';
    // La réservation du beforeEach a une date aléatoire : on la fixe dans le mois, hors du jour courant.
    $this->resa->update([
        'arrivee' => CarbonImmutable::parse('2026-11-05 14:00', $tz)->utc(),
        'depart' => CarbonImmutable::parse('2026-11-07 12:00', $tz)->utc(),
    ]);

    $mienne = Reservation::factory()->statut(StatutReservation::Confirmee)->create([
        'chambre_id' => $a->id,
        'arrivee' => CarbonImmutable::parse('2026-11-15 14:00', $tz)->utc(),
        'depart' => CarbonImmutable::parse('2026-11-20 12:00', $tz)->utc(),
    ]);
    Reservation::factory()->statut(StatutReservation::Confirmee)->create([
        'chambre_id' => $b->id,
        'arrivee' => CarbonImmutable::parse('2026-11-10 14:00', $tz)->utc(),
        'depart' => CarbonImmutable::parse('2026-11-14 12:00', $tz)->utc(),
    ]);
    Paiement::factory()->create(['reservation_id' => $mienne->id, 'montant' => 10000, 'paye_le' => now()]);
    Paiement::factory()->create(['reservation_id' => Reservation::where('chambre_id', $b->id)->first()->id, 'montant' => 7000, 'paye_le' => now()]);

    $gestionnaire = User::factory()->create()->assignRole(User::ROLE_GESTIONNAIRE);
    $gestionnaire->sites()->attach($site);
    $debut = CarbonImmutable::parse('2026-11-01', $tz);

    $siennes = new StatistiquesService($gestionnaire);
    // 5 nuitées / (2 chambres × 30 jours) = 8,3 %
    expect($siennes->tauxOccupation($debut, $debut->addMonth()))->toBe(8.3)
        ->and($siennes->arriveesDuJour())->toBe(1)
        ->and($siennes->revenusDuMois())->toBe(10000)
        ->and($siennes->parSiteDuMois())->toHaveCount(1);

    $tout = new StatistiquesService($this->admin);
    expect($tout->revenusDuMois())->toBe(17000)
        ->and($tout->arriveesDuJour())->toBe(1)
        ->and($tout->parSiteDuMois())->toHaveCount(3);
});

it('compte les demandes en attente et les impayés', function () {
    $resa = Reservation::factory()->statut(StatutReservation::Terminee)->create(['chambre_id' => $this->chambre->id, 'montant_ttc' => 80000]);
    Paiement::factory()->create(['reservation_id' => $resa->id, 'montant' => 30000]);

    $stats = new StatistiquesService($this->admin);

    expect($stats->enAttente())->toBe(1)->and($stats->impayes())->toBe(50000);
});

// --- Exports Excel -------------------------------------------------------------------------

it('exporte les réservations en Excel avec les bonnes colonnes', function () {
    $contenu = Excel::raw(new ReservationsExport(Reservation::query()), ExcelType::XLSX);
    $fichier = tempnam(sys_get_temp_dir(), 'xlsx');
    file_put_contents($fichier, $contenu);
    $feuille = IOFactory::load($fichier)->getActiveSheet();
    unlink($fichier);

    expect($feuille->getCell('A1')->getValue())->toBe('N° de fiche')
        ->and($feuille->getCell('A2')->getValue())->toBe($this->resa->numero)
        ->and($feuille->getCell('B2')->getValue())->toBe('En attente')
        ->and((int) $feuille->getCell('Q2')->getValue())->toBe(50000);
});

it('limite l\'export aux sites de l\'utilisateur (RG11)', function () {
    $autre = Reservation::factory()->create();
    $gestionnaire = User::factory()->create()->assignRole(User::ROLE_GESTIONNAIRE);
    $gestionnaire->sites()->attach($this->chambre->site_id);

    $contenu = Excel::raw(new ReservationsExport(Reservation::query()->accessiblesPar($gestionnaire)), ExcelType::XLSX);
    $fichier = tempnam(sys_get_temp_dir(), 'xlsx');
    file_put_contents($fichier, $contenu);
    $feuille = IOFactory::load($fichier)->getActiveSheet();
    unlink($fichier);

    expect($feuille->getHighestRow())->toBe(2)->and($feuille->getCell('A2')->getValue())->toBe($this->resa->numero)
        ->and($contenu)->not->toContain($autre->numero);
});

it('déclenche le téléchargement Excel depuis la liste des réservations et des paiements', function () {
    Excel::fake();
    $this->actingAs($this->admin);

    Livewire::test(ListReservations::class)->callAction('export');
    Excel::assertDownloaded('reservations-'.now('Africa/Niamey')->format('Y-m-d').'.xlsx');

    Livewire::test(ListPaiements::class)->callAction('export');
    Excel::assertDownloaded('paiements-'.now('Africa/Niamey')->format('Y-m-d').'.xlsx');
});

it('réserve l\'export à la permission exports.generer', function () {
    $this->admin->revokePermissionTo('exports.generer');
    $user = User::factory()->create()->assignRole(User::ROLE_ADMIN);
    $user->roles->first()->revokePermissionTo('exports.generer');
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    $this->actingAs($user->fresh());

    Livewire::test(ListReservations::class)->assertActionHidden('export');
});

// --- Sécurité ------------------------------------------------------------------------------

it('envoie les en-têtes de sécurité sur le site public', function () {
    $reponse = $this->get('/')->assertOk();

    expect($reponse->headers->get('X-Content-Type-Options'))->toBe('nosniff')
        ->and($reponse->headers->get('X-Frame-Options'))->toBe('SAMEORIGIN')
        ->and($reponse->headers->get('Referrer-Policy'))->toBe('strict-origin-when-cross-origin')
        ->and($reponse->headers->get('Permissions-Policy'))->toContain('camera=()')
        ->and($reponse->headers->get('Content-Security-Policy'))->toContain("frame-ancestors 'self'")->toContain("object-src 'none'");
});

it('applique les en-têtes de base au back-office sans CSP bloquante', function () {
    $reponse = $this->get('/admin/login')->assertOk();

    expect($reponse->headers->get('X-Frame-Options'))->toBe('SAMEORIGIN')
        ->and($reponse->headers->has('Content-Security-Policy'))->toBeFalse();
});

it('active HSTS sur les requêtes HTTPS', function () {
    $this->get('https://localhost/')->assertHeader('Strict-Transport-Security');
});
