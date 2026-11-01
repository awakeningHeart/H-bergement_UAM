<?php

use App\Enums\Repas;
use App\Enums\StatutReservation;
use App\Exceptions\ReservationException;
use App\Models\Chambre;
use App\Models\ChambreHold;
use App\Models\Client;
use App\Models\Reservation;
use App\Models\Sequence;
use App\Models\Site;
use App\Models\Tarif;
use App\Models\TarifRepas;
use App\Models\User;
use App\Services\HoldService;
use App\Services\Sms\SmsGateway;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Tests\Support\SmsCapture;

beforeEach(function () {
    $this->sms = new SmsCapture;
    app()->instance(SmsGateway::class, $this->sms);
    $this->chambre = Chambre::factory()->create();
    Tarif::factory()->create(['type_chambre_id' => $this->chambre->type_chambre_id, 'prix_nuit' => 25000, 'date_debut' => '2026-01-01']);
    TarifRepas::create(['repas' => Repas::PetitDejeuner, 'prix' => 2500]);
    TarifRepas::create(['repas' => Repas::Diner, 'prix' => 5000]);
    $this->arrivee = now('Africa/Niamey')->addDays(10)->toDateString();
    $this->depart = now('Africa/Niamey')->addDays(12)->toDateString();
    $this->holds = app(HoldService::class);
});

function hold(Chambre $chambre, string $arrivee, string $depart, ?string $jeton = null): string
{
    return app(HoldService::class)->creer($chambre->id, $arrivee, $depart, null, $jeton)['jeton'];
}

/** @return array<string, mixed> */
function charge(Chambre $chambre, string $jeton, string $arrivee, string $depart, array $surcharge = []): array
{
    return $surcharge + [
        'telephone' => '+227 90 12 34 56',
        'nom' => 'Moussa', 'prenom' => 'Aïchatou', 'type_piece' => 'Passeport', 'numero_piece' => 'P123',
        'email' => 'a@exemple.ne',
        'chambre_id' => $chambre->id, 'motif' => 'mission', 'arrivee' => $arrivee, 'heure' => '14:00', 'depart' => $depart,
        'repas' => ['petit_dejeuner', 'diner'],
        'payeur' => 'client', 'correspondant_nom' => 'Service accueil',
        'signature' => 'Aïchatou Moussa', 'conditions' => true,
        'hold_token' => $jeton, 'idempotence' => 'clef-'.$jeton,
    ];
}

// --- Lookup et fuite de données -------------------------------------------------------------

it('répond au lookup par {known} uniquement', function () {
    Client::factory()->create(['telephone' => '+22796000000', 'nom' => 'Secret']);

    $connu = $this->postJson('/api/clients/lookup', ['telephone' => '96 00 00 00'])->assertOk()->assertExactJson(['known' => true]);
    $this->postJson('/api/clients/lookup', ['telephone' => '+227 90 99 99 99'])->assertOk()->assertExactJson(['known' => false]);

    expect($connu->getContent())->not->toContain('Secret');
});

it('refuse un numéro invalide au lookup et le limite en débit', function () {
    $this->postJson('/api/clients/lookup', ['telephone' => '12'])->assertUnprocessable();
    $this->postJson('/api/clients/lookup', [])->assertUnprocessable();

    foreach (range(1, 20) as $i) {
        $this->postJson('/api/clients/lookup', ['telephone' => '90 99 99 99']);
    }
    $this->postJson('/api/clients/lookup', ['telephone' => '90 99 99 99'])->assertStatus(429);
});

it('accepte les numéros internationaux et les normalise en E.164', function () {
    Client::factory()->create(['telephone' => '+2348031234567']);

    $this->postJson('/api/clients/lookup', ['telephone' => '+234 803 123 4567'])->assertExactJson(['known' => true]);
});

// --- Holds ---------------------------------------------------------------------------------

it('pose un hold de 15 minutes et empêche un second navigateur de prendre la chambre', function () {
    $a = $this->postJson('/api/holds', ['chambre_id' => $this->chambre->id, 'arrivee' => $this->arrivee, 'depart' => $this->depart])
        ->assertCreated();
    $b = $this->postJson('/api/holds', ['chambre_id' => $this->chambre->id, 'arrivee' => $this->arrivee, 'depart' => $this->depart])
        ->assertStatus(409);

    expect(ChambreHold::count())->toBe(1)
        ->and(ChambreHold::first()->token)->not->toBe($a->json('hold_token'))
        ->and(ChambreHold::first()->expire_le->diffInMinutes(now(), true))->toBeLessThanOrEqual(15);
});

it('libère le hold quand on change de chambre et à son expiration', function () {
    $jeton = hold($this->chambre, $this->arrivee, $this->depart);

    $this->deleteJson('/api/holds', ['hold_token' => $jeton])->assertOk();
    expect(ChambreHold::count())->toBe(0);

    hold($this->chambre, $this->arrivee, $this->depart);
    $this->travel(16)->minutes();
    expect(app(HoldService::class)->purger())->toBe(1)->and(ChambreHold::count())->toBe(0);
});

it('purge les holds expirés via la commande planifiée', function () {
    hold($this->chambre, $this->arrivee, $this->depart);
    $this->travel(20)->minutes();

    Artisan::call('holds:purger');

    expect(ChambreHold::count())->toBe(0);
});

it('remplace le hold du même navigateur lors d\'un changement de dates', function () {
    $jeton = hold($this->chambre, $this->arrivee, $this->depart);
    $nouveau = hold($this->chambre, $this->arrivee, now('Africa/Niamey')->addDays(13)->toDateString(), $jeton);

    expect(ChambreHold::count())->toBe(1)
        ->and(app(HoldService::class)->valider($jeton))->toBeNull()
        ->and(app(HoldService::class)->valider($nouveau))->not->toBeNull();
});

it('stocke l\'empreinte du jeton de hold, jamais le jeton', function () {
    $jeton = hold($this->chambre, $this->arrivee, $this->depart);

    expect(ChambreHold::first()->token)->toBe(hash('sha256', $jeton))->not->toBe($jeton);
});

// --- Estimation ----------------------------------------------------------------------------

it('estime HT, TVA et TTC', function () {
    $this->postJson('/api/reservations/estimate', [
        'chambre_id' => $this->chambre->id, 'motif' => 'mission', 'arrivee' => $this->arrivee, 'heure' => '14:00',
        'depart' => $this->depart, 'repas' => ['petit_dejeuner'],
    ])->assertOk()
        ->assertJsonPath('nuitees', 2)
        ->assertJsonPath('montant_ht', 55000)   // 2 × 25 000 + 2 × 2 500
        ->assertJsonPath('montant_tva', 0)
        ->assertJsonPath('montant_ttc', 55000);
});

// --- Création ------------------------------------------------------------------------------

it('crée la réservation avec un numéro séquentiel UAM-MH-AAAA-NNNNN, un jeton haché et un SMS', function () {
    $jeton = hold($this->chambre, $this->arrivee, $this->depart);

    $reponse = $this->postJson('/reservations', charge($this->chambre, $jeton, $this->arrivee, $this->depart))->assertCreated();

    $annee = now('Africa/Niamey')->year;
    $reservation = Reservation::firstOrFail();
    expect($reponse->json('numero'))->toBe(sprintf('UAM-MH-%d-00001', $annee))
        ->and($reservation->statut)->toBe(StatutReservation::EnAttente)
        ->and($reservation->nuitees)->toBe(2)
        ->and($reservation->montant_ttc)->toBe(65000)   // 2 × 25 000 + 2 × 2 500 + 2 × 5 000
        ->and($reservation->repas)->toHaveCount(2)
        ->and($reservation->client->telephone)->toBe('+22790123456')
        ->and($reservation->jeton_suivi_hash)->toHaveLength(64)
        ->and($reservation->conditions_acceptees_le)->not->toBeNull()
        ->and(ChambreHold::count())->toBe(0);

    // Le jeton en clair n'est que dans le SMS ; seule son empreinte est en base.
    preg_match('#/suivi/([A-Za-z0-9]{40})#', $this->sms->dernier()['message'], $m);
    expect($m[1])->not->toBe($reservation->jeton_suivi_hash)
        ->and(hash('sha256', $m[1]))->toBe($reservation->jeton_suivi_hash)
        ->and($this->sms->dernier()['telephone'])->toBe('+22790123456')
        ->and($this->sms->dernier()['message'])->toContain($reservation->numero);
});

it('attribue des numéros consécutifs', function () {
    $autre = Chambre::factory()->create();
    Tarif::factory()->create(['type_chambre_id' => $autre->type_chambre_id]);

    $a = $this->postJson('/reservations', charge($this->chambre, hold($this->chambre, $this->arrivee, $this->depart), $this->arrivee, $this->depart))->json('numero');
    $b = $this->postJson('/reservations', charge($autre, hold($autre, $this->arrivee, $this->depart), $this->arrivee, $this->depart, ['telephone' => '+227 90 55 55 55', 'idempotence' => 'autre']))->json('numero');

    expect(substr($a, -5))->toBe('00001')->and(substr($b, -5))->toBe('00002');
});

it('est idempotent : un double envoi ne crée qu\'une réservation', function () {
    $jeton = hold($this->chambre, $this->arrivee, $this->depart);
    $donnees = charge($this->chambre, $jeton, $this->arrivee, $this->depart);

    $premiere = $this->postJson('/reservations', $donnees)->assertCreated();
    $seconde = $this->postJson('/reservations', $donnees)->assertOk();

    expect(Reservation::count())->toBe(1)
        ->and($seconde->json('numero'))->toBe($premiere->json('numero'))
        ->and($seconde->json('suivi_url'))->toBeNull()
        ->and($this->sms->envoyes)->toHaveCount(1);
});

it('refuse la création quand le hold a expiré', function () {
    $jeton = hold($this->chambre, $this->arrivee, $this->depart);
    $this->travel(16)->minutes();

    $this->postJson('/reservations', charge($this->chambre, $jeton, $this->arrivee, $this->depart))
        ->assertStatus(409)
        ->assertJsonPath('raison', ReservationException::HOLD_EXPIRE);

    expect(Reservation::count())->toBe(0);
});

it('refuse un jeton de hold appartenant à une autre chambre ou à d\'autres dates', function () {
    $autre = Chambre::factory()->create();
    Tarif::factory()->create(['type_chambre_id' => $autre->type_chambre_id]);
    $jetonAutre = hold($autre, $this->arrivee, $this->depart);

    $this->postJson('/reservations', charge($this->chambre, $jetonAutre, $this->arrivee, $this->depart))->assertStatus(409);

    $jeton = hold($this->chambre, $this->arrivee, $this->depart);
    $this->postJson('/reservations', charge($this->chambre, $jeton, $this->arrivee, now('Africa/Niamey')->addDays(14)->toDateString()))->assertStatus(409);
    expect(Reservation::count())->toBe(0);
});

it('un seul de deux navigateurs réussit sur la même chambre et les mêmes dates', function () {
    $a = hold($this->chambre, $this->arrivee, $this->depart);

    // Le second navigateur ne peut plus obtenir de hold.
    expect(fn () => hold($this->chambre, $this->arrivee, $this->depart))->toThrow(ReservationException::class);

    $this->postJson('/reservations', charge($this->chambre, $a, $this->arrivee, $this->depart))->assertCreated();

    // Une fois réservée, la chambre reste indisponible.
    expect(fn () => hold($this->chambre, $this->arrivee, $this->depart))->toThrow(ReservationException::class);
    expect(Reservation::count())->toBe(1);
});

it('revérifie la disponibilité sous verrou même avec un hold valide', function () {
    $jeton = hold($this->chambre, $this->arrivee, $this->depart);

    // Une réservation concurrente est insérée directement (contournant le hold).
    Reservation::factory()->create([
        'chambre_id' => $this->chambre->id,
        'arrivee' => now('Africa/Niamey')->addDays(10)->setTime(15, 0)->utc(),
        'depart' => now('Africa/Niamey')->addDays(12)->setTime(10, 0)->utc(),
        'statut' => StatutReservation::Confirmee,
    ]);

    $this->postJson('/reservations', charge($this->chambre, $jeton, $this->arrivee, $this->depart))
        ->assertStatus(409)
        ->assertJsonPath('raison', ReservationException::INDISPONIBLE);
    expect(Reservation::count())->toBe(1);
});

it('annule toute la transaction en cas d\'échec (aucun client orphelin)', function () {
    $jeton = hold($this->chambre, $this->arrivee, $this->depart);
    Tarif::query()->delete();

    $this->postJson('/reservations', charge($this->chambre, $jeton, $this->arrivee, $this->depart))->assertStatus(409);

    expect(Client::where('telephone', '+22790123456')->exists())->toBeFalse()
        ->and(Reservation::count())->toBe(0);
});

it('exige la vérification OTP pour réserver avec un numéro déjà connu', function () {
    Client::factory()->create(['telephone' => '+22790123456', 'nom' => 'Ancien']);
    $jeton = hold($this->chambre, $this->arrivee, $this->depart);

    $this->postJson('/reservations', charge($this->chambre, $jeton, $this->arrivee, $this->depart))
        ->assertForbidden()
        ->assertJsonPath('raison', ReservationException::VERIFICATION);

    expect(Client::where('telephone', '+22790123456')->first()->nom)->toBe('Ancien');

    $this->withSession(['otp.verifies.'.sha1('+22790123456') => now()->addMinutes(10)->getTimestamp()])
        ->postJson('/reservations', charge($this->chambre, $jeton, $this->arrivee, $this->depart))
        ->assertCreated();

    // L'identité du client connu est mise à jour après vérification.
    expect(Client::where('telephone', '+22790123456')->first()->nom)->toBe('Moussa')
        ->and(Client::where('telephone', '+22790123456')->count())->toBe(1);
});

it('valide chaque fenêtre côté serveur', function () {
    $this->withoutMiddleware(ThrottleRequests::class);
    $jeton = hold($this->chambre, $this->arrivee, $this->depart);
    $base = charge($this->chambre, $jeton, $this->arrivee, $this->depart);

    foreach ([
        'nom' => ['nom' => ''],
        'telephone' => ['telephone' => 'abc'],
        'depart' => ['depart' => $this->arrivee],
        'arrivee' => ['arrivee' => '2020-01-01'],
        'motif' => ['motif' => 'vacances'],
        'repas.0' => ['repas' => ['gouter']],
        'organisme_id' => ['payeur' => 'organisme'],
        'signature' => ['signature' => 'ab'],
        'conditions' => ['conditions' => false],
        'email' => ['email' => 'pas-un-email'],
    ] as $champ => $surcharge) {
        $this->postJson('/reservations', $surcharge + $base)->assertUnprocessable()->assertJsonValidationErrors([$champ]);
    }
    expect(Reservation::count())->toBe(0);
});

it('limite le débit de création de réservations', function () {
    foreach (range(1, 5) as $i) {
        $this->postJson('/reservations', []);
    }

    $this->postJson('/reservations', [])->assertStatus(429);
});

// --- Fiche PDF, suivi, QR code -------------------------------------------------------------

function reservationCreee(object $test): array
{
    $jeton = hold($test->chambre, $test->arrivee, $test->depart);
    $reponse = $test->postJson('/reservations', charge($test->chambre, $jeton, $test->arrivee, $test->depart))->assertCreated();

    return [Reservation::firstOrFail(), $reponse->json('suivi_url'), $reponse->json('fiche_pdf_url')];
}

it('sert la fiche PDF uniquement via un lien signé', function () {
    [$reservation, , $lienSigne] = reservationCreee($this);

    $this->get(route('fiche.pdf', $reservation->numero))->assertForbidden();
    $reponse = $this->get($lienSigne)->assertOk();

    expect($reponse->headers->get('content-type'))->toContain('application/pdf')
        ->and(substr($reponse->getContent(), 0, 4))->toBe('%PDF');
});

it('refuse un lien de fiche modifié ou expiré', function () {
    [$reservation] = reservationCreee($this);

    $this->get(URL::signedRoute('fiche.pdf', ['numero' => 'UAM-MH-2026-99999']))->assertNotFound();
    $this->get(route('fiche.pdf', ['numero' => $reservation->numero, 'signature' => 'faux']))->assertForbidden();

    $expire = URL::temporarySignedRoute('fiche.pdf', now()->addMinute(), ['numero' => $reservation->numero]);
    $this->travel(5)->minutes();
    $this->get($expire)->assertForbidden();
});

it('affiche le suivi par lien opaque et permet l\'annulation', function () {
    [$reservation, $lien] = reservationCreee($this);

    $this->get($lien)->assertOk()->assertSee($reservation->numero)->assertSee('En attente');
    $this->get('/suivi/'.str_repeat('a', 40))->assertNotFound();

    $this->post($lien.'/annuler')->assertRedirect();
    expect($reservation->fresh()->statut)->toBe(StatutReservation::Annulee);

    // Déjà annulée : la seconde annulation est refusée.
    $this->post($lien.'/annuler')->assertSessionHasErrors('annulation');
});

it('ne permet pas d\'annuler une réservation en cours', function () {
    [$reservation, $lien] = reservationCreee($this);
    $reservation->update(['statut' => StatutReservation::EnCours]);

    $this->post($lien.'/annuler')->assertSessionHasErrors('annulation');
    expect($reservation->fresh()->statut)->toBe(StatutReservation::EnCours);
});

it('ouvre la fiche pour le personnel via le QR code et refuse un jeton falsifié', function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    [$reservation] = reservationCreee($this);
    $admin = User::factory()->create()->assignRole(User::ROLE_ADMIN);

    $this->get(route('admin.scan', $reservation->tokenScan()))->assertRedirect(route('filament.admin.auth.login'));

    $this->actingAs($admin)->get(route('admin.scan', $reservation->tokenScan()))
        ->assertRedirect(route('filament.admin.resources.reservations.edit', $reservation));
    $this->get(route('admin.scan', $reservation->numero.'.faux'))->assertNotFound();
    $this->get(route('admin.scan', 'nimporte-quoi'))->assertNotFound();
});

it('applique RG11 au scan : un gestionnaire ne voit pas les autres sites', function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    [$reservation] = reservationCreee($this);
    $autreSite = Site::factory()->create();
    $gestionnaire = User::factory()->create()->assignRole(User::ROLE_GESTIONNAIRE);
    $gestionnaire->sites()->attach($autreSite);

    $this->actingAs($gestionnaire)->get(route('admin.scan', $reservation->tokenScan()))->assertForbidden();

    $gestionnaire->sites()->attach($this->chambre->site_id);
    $this->actingAs($gestionnaire)->get(route('admin.scan', $reservation->tokenScan()))->assertRedirect();
});

it('numérote sans doublon de manière séquentielle sous charge', function () {
    $numeros = collect(range(1, 25))->map(fn () => Sequence::suivante('fiche', 2026));

    expect($numeros->unique())->toHaveCount(25)->and($numeros->all())->toBe(range(1, 25));
    expect(DB::table('sequences')->where('cle', 'fiche')->value('valeur'))->toBe(25);
});

it('n\'expose aucune route API de réservation sans limitation de débit', function () {
    foreach (['api.clients.lookup', 'api.otp.send', 'api.otp.verify', 'api.holds.store', 'reservations.store'] as $nom) {
        $middlewares = collect(app('router')->getRoutes()->getByName($nom)->gatherMiddleware());
        expect($middlewares->contains(fn ($m) => str_starts_with($m, 'throttle:')))->toBeTrue($nom);
    }
});

// --- Anti-saturation des holds --------------------------------------------------------------

it('plafonne le nombre de chambres qu\'un même visiteur peut bloquer', function () {
    $chambres = Chambre::factory()->count(4)->create();

    foreach ($chambres->take(3) as $c) {
        hold($c, $this->arrivee, $this->depart);
    }

    expect(fn () => hold($chambres[3], $this->arrivee, $this->depart))->toThrow(ReservationException::class, 'plusieurs chambres');
    expect(ChambreHold::count())->toBe(3);
});

it('permet de remplacer son propre hold sans atteindre le plafond', function () {
    $chambres = Chambre::factory()->count(3)->create();
    $jetons = $chambres->map(fn ($c) => hold($c, $this->arrivee, $this->depart));

    $nouveau = hold($chambres[0], $this->arrivee, now('Africa/Niamey')->addDays(13)->toDateString(), $jetons[0]);

    expect(ChambreHold::count())->toBe(3)->and($nouveau)->not->toBe($jetons[0]);
});

it('libère le plafond quand les holds expirent', function () {
    $chambres = Chambre::factory()->count(4)->create();
    foreach ($chambres->take(3) as $c) {
        hold($c, $this->arrivee, $this->depart);
    }

    $this->travel(16)->minutes();

    expect(hold($chambres[3], $this->arrivee, $this->depart))->toBeString();
});
