<?php

use App\Enums\StatutReservation;
use App\Exceptions\ReservationException;
use App\Models\AuditLog;
use App\Models\Chambre;
use App\Models\Parametre;
use App\Models\Reservation;
use App\Models\Site;
use App\Models\Tarif;
use App\Models\User;
use App\Services\AvailabilityService;
use App\Services\ReservationService;
use App\Services\ReservationWorkflow;
use App\Services\Sms\SmsGateway;
use Carbon\CarbonImmutable;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Facades\Artisan;
use Tests\Support\SmsCapture;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->sms = new SmsCapture;
    app()->instance(SmsGateway::class, $this->sms);
    $this->workflow = app(ReservationWorkflow::class);
    $this->admin = User::factory()->create()->assignRole(User::ROLE_ADMIN);
    $this->chambre = Chambre::factory()->create();
    Tarif::factory()->create(['type_chambre_id' => $this->chambre->type_chambre_id]);
});

function resa(Chambre $chambre, StatutReservation $statut = StatutReservation::EnAttente, array $attributs = []): Reservation
{
    return Reservation::factory()->statut($statut)->create(['chambre_id' => $chambre->id] + $attributs);
}

it('applique le graphe de transitions du cahier des règles de gestion', function (StatutReservation $de, StatutReservation $vers, bool $permis) {
    $reservation = resa($this->chambre, $de);

    if ($permis) {
        expect($this->workflow->transitionner($reservation, $vers, $this->admin)->statut)->toBe($vers);
    } else {
        expect(fn () => $this->workflow->transitionner($reservation, $vers, $this->admin))->toThrow(ReservationException::class);
        expect($reservation->fresh()->statut)->toBe($de);
    }
})->with([
    'attente → confirmée' => [StatutReservation::EnAttente, StatutReservation::Confirmee, true],
    'attente → refusée' => [StatutReservation::EnAttente, StatutReservation::Refusee, true],
    'attente → expirée' => [StatutReservation::EnAttente, StatutReservation::Expiree, true],
    'attente → annulée' => [StatutReservation::EnAttente, StatutReservation::Annulee, true],
    'confirmée → en cours' => [StatutReservation::Confirmee, StatutReservation::EnCours, true],
    'confirmée → annulée' => [StatutReservation::Confirmee, StatutReservation::Annulee, true],
    'confirmée → non présenté' => [StatutReservation::Confirmee, StatutReservation::NonPresente, true],
    'en cours → terminée' => [StatutReservation::EnCours, StatutReservation::Terminee, true],
    'attente → en cours (interdit)' => [StatutReservation::EnAttente, StatutReservation::EnCours, false],
    'attente → terminée (interdit)' => [StatutReservation::EnAttente, StatutReservation::Terminee, false],
    'confirmée → refusée (interdit)' => [StatutReservation::Confirmee, StatutReservation::Refusee, false],
    'en cours → annulée (interdit)' => [StatutReservation::EnCours, StatutReservation::Annulee, false],
    'terminée → en cours (interdit)' => [StatutReservation::Terminee, StatutReservation::EnCours, false],
    'refusée → confirmée (interdit)' => [StatutReservation::Refusee, StatutReservation::Confirmee, false],
    'annulée → confirmée (interdit)' => [StatutReservation::Annulee, StatutReservation::Confirmee, false],
]);

it('journalise chaque transition avec son auteur', function () {
    $reservation = resa($this->chambre);

    $this->workflow->transitionner($reservation, StatutReservation::Refusee, $this->admin, 'Site complet');

    $entree = AuditLog::query()->where('event', 'statut')->latest('id')->first();
    expect($entree->causer_id)->toBe($this->admin->id)
        ->and($entree->subject_id)->toBe((string) $reservation->id)
        ->and($entree->properties['de'])->toBe('en_attente')
        ->and($entree->properties['vers'])->toBe('refusee')
        ->and($entree->properties['motif'])->toBe('Site complet')
        ->and($entree->description)->toContain('En attente')
        ->and($reservation->fresh()->refus_motif)->toBe('Site complet');
});

it('envoie un SMS à la confirmation et au refus (avec motif)', function () {
    $a = resa($this->chambre);
    $b = resa(Chambre::factory()->create());

    $this->workflow->transitionner($a, StatutReservation::Confirmee, $this->admin);
    expect($this->sms->dernier()['message'])->toContain($a->numero)->toContain('confirmée')
        ->and($this->sms->dernier()['telephone'])->toBe($a->client->telephone);

    $this->workflow->transitionner($b, StatutReservation::Refusee, $this->admin, 'Travaux en cours');
    expect($this->sms->dernier()['message'])->toContain($b->numero)->toContain('Travaux en cours');
});

it('n\'envoie pas de SMS pour une annulation demandée par le client', function () {
    $reservation = resa($this->chambre);

    app(ReservationService::class)->annuler($reservation);

    expect($this->sms->envoyes)->toBeEmpty()
        ->and($reservation->fresh()->statut)->toBe(StatutReservation::Annulee)
        ->and(AuditLog::where('event', 'statut')->count())->toBe(1);
});

it('enregistre la remise des clés et émet la facture à l\'arrivée', function () {
    $reservation = resa($this->chambre, StatutReservation::Confirmee);

    $this->workflow->transitionner($reservation, StatutReservation::EnCours, $this->admin);

    $reservation->refresh();
    expect($reservation->cles_remises_le)->not->toBeNull()
        ->and($reservation->factures()->count())->toBe(1)
        ->and($reservation->echeancePaiement()->equalTo(CarbonImmutable::instance($reservation->cles_remises_le)->addHours(48)))->toBeTrue();
});

it('enregistre le départ effectif', function () {
    $reservation = resa($this->chambre, StatutReservation::EnCours);

    $this->workflow->transitionner($reservation, StatutReservation::Terminee, $this->admin);

    expect($reservation->fresh()->depart_effectif_le)->not->toBeNull();
});

it('refuse la transition à un utilisateur sans droit ou hors de son site (RG11)', function () {
    $reservation = resa($this->chambre);
    $direction = User::factory()->create()->assignRole(User::ROLE_DIRECTION);
    $gestionnaire = User::factory()->create()->assignRole(User::ROLE_GESTIONNAIRE);
    $gestionnaire->sites()->attach(Site::factory()->create());

    expect(fn () => $this->workflow->transitionner($reservation, StatutReservation::Confirmee, $direction))->toThrow(ReservationException::class)
        ->and(fn () => $this->workflow->transitionner($reservation, StatutReservation::Confirmee, $gestionnaire))->toThrow(ReservationException::class);

    $gestionnaire->sites()->attach($this->chambre->site_id);
    expect($this->workflow->transitionner($reservation, StatutReservation::Confirmee, $gestionnaire)->statut)->toBe(StatutReservation::Confirmee);
});

// --- Expirations et non-présentation (planificateur) --------------------------------------------

it('expire les demandes en attente depuis plus de 24 h (RG09) et libère la chambre', function () {
    $ancienne = resa($this->chambre, StatutReservation::EnAttente, [
        'arrivee' => now()->addDays(5), 'depart' => now()->addDays(7), 'created_at' => now()->subHours(25),
    ]);
    $recente = resa(Chambre::factory()->create(), StatutReservation::EnAttente, ['created_at' => now()->subHours(23)]);

    expect(app(AvailabilityService::class)->estDisponible($this->chambre, now()->addDays(5)->toDateString(), now()->addDays(7)->toDateString()))->toBeFalse();

    Artisan::call('reservations:expirer');

    expect($ancienne->fresh()->statut)->toBe(StatutReservation::Expiree)
        ->and($recente->fresh()->statut)->toBe(StatutReservation::EnAttente)
        ->and($this->sms->dernier()['message'])->toContain($ancienne->numero)->toContain('expiré')
        ->and(app(AvailabilityService::class)->estDisponible($this->chambre, now()->addDays(5)->toDateString(), now()->addDays(7)->toDateString()))->toBeTrue();
});

it('lit le délai d\'expiration dans les paramètres', function () {
    Parametre::create(['cle' => 'expiration_attente_heures', 'valeur' => 2]);
    $reservation = resa($this->chambre, StatutReservation::EnAttente, ['created_at' => now()->subHours(3)]);

    expect($this->workflow->expirerEnAttente())->toBe(1)->and($reservation->fresh()->statut)->toBe(StatutReservation::Expiree);
});

it('marque non présentée une arrivée non effectuée le lendemain après 12:00 (RG10)', function () {
    $tz = 'Africa/Niamey';
    // Arrivée prévue hier 14:00 (heure de Niamey).
    $hier = CarbonImmutable::now($tz)->subDay()->setTime(14, 0)->utc();
    $reservation = resa($this->chambre, StatutReservation::Confirmee, ['arrivee' => $hier, 'depart' => $hier->addDays(2)]);
    $aujourdhui = CarbonImmutable::now($tz)->setTime(14, 0)->utc();
    $presente = resa(Chambre::factory()->create(), StatutReservation::Confirmee, ['arrivee' => $aujourdhui, 'depart' => $aujourdhui->addDays(2)]);
    $enCours = resa(Chambre::factory()->create(), StatutReservation::EnCours, ['arrivee' => $hier, 'depart' => $hier->addDays(2)]);

    // Aujourd'hui 11:00 : le délai (lendemain 12:00) n'est pas échu.
    $this->travelTo(CarbonImmutable::now($tz)->setTime(11, 0)->utc());
    expect($this->workflow->marquerNonPresentes())->toBe(0);

    // Aujourd'hui 12:30 : échu.
    $this->travelTo(CarbonImmutable::now($tz)->setTime(12, 30)->utc());
    expect($this->workflow->marquerNonPresentes())->toBe(1)
        ->and($reservation->fresh()->statut)->toBe(StatutReservation::NonPresente)
        ->and($presente->fresh()->statut)->toBe(StatutReservation::Confirmee)
        ->and($enCours->fresh()->statut)->toBe(StatutReservation::EnCours);

    // La chambre est libérée.
    expect(app(AvailabilityService::class)->estDisponible($this->chambre, $hier->toDateString(), $hier->addDays(2)->toDateString()))->toBeTrue();
});

it('planifie les tâches de maintenance (holds, expirations, non-présentés, OTP)', function () {
    $this->artisan('schedule:list')
        ->expectsOutputToContain('holds:purger')
        ->expectsOutputToContain('reservations:expirer')
        ->expectsOutputToContain('reservations:non-presentes')
        ->expectsOutputToContain('otp:purger')
        ->assertSuccessful();
});
