<?php

use App\Enums\Repas;
use App\Livewire\ReservationModal;
use App\Livewire\SuiviTelephone;
use App\Models\Chambre;
use App\Models\ChambreHold;
use App\Models\Client;
use App\Models\Organisme;
use App\Models\Reservation;
use App\Models\Tarif;
use App\Models\TarifRepas;
use App\Services\HoldService;
use App\Services\Sms\SmsGateway;
use Livewire\Livewire;
use Tests\Support\SmsCapture;

beforeEach(function () {
    $this->sms = new SmsCapture;
    app()->instance(SmsGateway::class, $this->sms);
    $this->chambre = Chambre::factory()->create();
    Tarif::factory()->create(['type_chambre_id' => $this->chambre->type_chambre_id, 'prix_nuit' => 25000, 'date_debut' => '2026-01-01']);
    TarifRepas::create(['repas' => Repas::PetitDejeuner, 'prix' => 2500]);
    $this->arrivee = now('Africa/Niamey')->addDays(10)->toDateString();
    $this->depart = now('Africa/Niamey')->addDays(12)->toDateString();
});

function modalOuvert(object $test)
{
    return Livewire::test(ReservationModal::class)
        ->dispatch('reservation-ouvrir', chambre: $test->chambre->id, arrivee: $test->arrivee, depart: $test->depart);
}

it('ouvre le modal, bloque la chambre et affiche la chambre choisie', function () {
    modalOuvert($this)
        ->assertSet('ouvert', true)
        ->assertSet('etape', 1)
        ->assertSee($this->chambre->site->nom)
        ->assertSee('Ch. '.$this->chambre->numero)
        ->assertSee('Changer de chambre');

    expect(ChambreHold::count())->toBe(1);
});

it('parcourt les trois fenêtres et crée la réservation pour un nouveau client', function () {
    $organisme = Organisme::factory()->create();

    modalOuvert($this)
        ->set('telephone', '90 12 34 56')->call('verifierTelephone')
        ->assertSet('telephone', '+22790123456')
        ->assertSet('etatTelephone', 'nouveau')
        ->set('nom', 'Moussa')->set('prenom', 'Aïchatou')
        ->call('continuer')->assertSet('etape', 2)->assertHasNoErrors()
        ->set('motif', 'colloque')
        ->call('choisirRepas', true)->call('basculerRepas', 'petit_dejeuner')
        ->call('continuer')->assertSet('etape', 3)
        ->assertSee('55')   // 2 × 25 000 + 2 × 2 500 = 55 000
        ->set('payeur', 'organisme')->set('organisme_id', $organisme->id)
        ->set('signature', 'Aïchatou Moussa')->set('conditions', true)
        ->call('confirmer')
        ->assertHasNoErrors()
        ->assertSet('etape', 4)
        ->assertSee('UAM-MH-');

    $reservation = Reservation::with('client')->firstOrFail();
    expect($reservation->motif->value)->toBe('colloque')
        ->and($reservation->organisme_id)->toBe($organisme->id)
        ->and($reservation->montant_ttc)->toBe(55000)
        ->and($reservation->client->telephone)->toBe('+22790123456')
        ->and(ChambreHold::count())->toBe(0);
});

it('valide la fenêtre 1 avant de passer à la suivante', function () {
    modalOuvert($this)
        ->set('telephone', '90 12 34 56')->call('verifierTelephone')
        ->call('continuer')
        ->assertHasErrors(['nom', 'prenom'])
        ->assertSet('etape', 1);
});

it('signale un numéro invalide', function () {
    modalOuvert($this)->set('telephone', '12')->call('verifierTelephone')->assertSet('etatTelephone', 'invalide')->assertSee('Numéro invalide');
});

it('n\'expose aucune donnée personnelle d\'un client connu avant validation du code', function () {
    Client::factory()->create(['telephone' => '+22796000000', 'nom' => 'Hamidou', 'prenom' => 'Zeinabou', 'email' => 'secret@exemple.ne']);

    $composant = modalOuvert($this)
        ->set('telephone', '96 00 00 00')->call('verifierTelephone')
        ->assertSet('etatTelephone', 'connu')
        ->assertSet('nom', '')->assertSet('prenom', '')->assertSet('email', '')
        ->assertDontSee('Hamidou')->assertDontSee('secret@exemple.ne');

    expect($this->sms->envoyes)->toHaveCount(1)
        ->and(json_encode($composant->snapshot ?? []))->not->toContain('Hamidou');

    // Un mauvais code ne dévoile rien non plus.
    $composant->set('code', '000000')->call('verifierCode')->assertSet('nom', '')->assertSee('Code incorrect');

    // Le bon code préremplit l'identité.
    $composant->set('code', $this->sms->dernierCode())->call('verifierCode')
        ->assertSet('etatTelephone', 'verifie')
        ->assertSet('nom', 'Hamidou')->assertSet('prenom', 'Zeinabou');
});

it('empêche de continuer tant que le code d\'un client connu n\'est pas validé', function () {
    Client::factory()->create(['telephone' => '+22796000000']);

    modalOuvert($this)
        ->set('telephone', '96 00 00 00')->call('verifierTelephone')
        ->set('nom', 'Intrus')->set('prenom', 'Pirate')
        ->call('continuer')
        ->assertSet('etape', 1)
        ->assertHasErrors('telephone');
});

it('revient au choix de chambre quand le hold expire pendant la saisie', function () {
    $composant = modalOuvert($this)
        ->set('telephone', '90 12 34 56')->call('verifierTelephone')->set('nom', 'Moussa')->set('prenom', 'Aïchatou');

    $this->travel(16)->minutes();

    $composant->call('continuer')
        ->assertSet('etape', 0)
        ->assertSee('délai de réservation temporaire');
    expect(Reservation::count())->toBe(0);
});

it('libère le hold avec « Changer de chambre »', function () {
    modalOuvert($this)->call('fermer')->assertSet('ouvert', false);

    expect(ChambreHold::count())->toBe(0);
});

it('ne peut pas être ouvert sur une chambre déjà bloquée par un autre navigateur', function () {
    app(HoldService::class)->creer($this->chambre->id, $this->arrivee, $this->depart);

    modalOuvert($this)->assertSet('etape', 0)->assertSee('plus disponible');
});

it('ne crée qu\'une réservation en cas de double clic sur « Confirmer »', function () {
    $composant = modalOuvert($this)
        ->set('telephone', '90 12 34 56')->call('verifierTelephone')->set('nom', 'Moussa')->set('prenom', 'Aïchatou')
        ->call('continuer')->call('continuer')
        ->set('signature', 'Aïchatou Moussa')->set('conditions', true);

    $composant->call('confirmer')->call('confirmer');

    expect(Reservation::count())->toBe(1)->and($this->sms->envoyes)->toHaveCount(1);
});

it('exige les conditions et la signature à la fenêtre 3', function () {
    modalOuvert($this)
        ->set('telephone', '90 12 34 56')->call('verifierTelephone')->set('nom', 'Moussa')->set('prenom', 'Aïchatou')
        ->call('continuer')->call('continuer')
        ->call('confirmer')
        ->assertHasErrors(['conditions', 'signature'])
        ->assertSet('etape', 3);
});

it('recalcule les nuitées quand les dates changent et repose le hold', function () {
    $composant = modalOuvert($this)
        ->set('telephone', '90 12 34 56')->call('verifierTelephone')->set('nom', 'Moussa')->set('prenom', 'Aïchatou')
        ->call('continuer')
        ->set('depart', now('Africa/Niamey')->addDays(14)->toDateString())
        ->call('continuer')
        ->assertSet('etape', 3);

    expect(ChambreHold::count())->toBe(1)
        ->and(ChambreHold::first()->depart->toDateString())->toBe(now('Africa/Niamey')->addDays(14)->toDateString());
});

// --- Suivi par téléphone + OTP ------------------------------------------------------------

it('permet de retrouver et d\'annuler ses réservations par téléphone et code', function () {
    $client = Client::factory()->create(['telephone' => '+22796000000']);
    $mienne = Reservation::factory()->create(['client_id' => $client->id, 'chambre_id' => $this->chambre->id]);
    $autre = Reservation::factory()->create();

    $composant = Livewire::test(SuiviTelephone::class)
        ->set('telephone', '96 00 00 00')->call('envoyerCode')
        ->assertSet('etape', 'code')
        ->assertDontSee($mienne->numero);

    $composant->set('code', '000000')->call('verifier')->assertSet('etape', 'code')->assertDontSee($mienne->numero);

    $composant->set('code', $this->sms->dernierCode())->call('verifier')
        ->assertSet('etape', 'liste')
        ->assertSee($mienne->numero)
        ->assertDontSee($autre->numero)
        ->call('annuler', $mienne->id)
        ->assertSee('annulée');

    expect($mienne->fresh()->statut->value)->toBe('annulee');

    // Impossible d'annuler la réservation d'un autre client.
    $composant->call('annuler', $autre->id);
    expect($autre->fresh()->statut->value)->toBe('en_attente');
});

it('répond de la même façon pour un numéro inconnu au suivi', function () {
    Livewire::test(SuiviTelephone::class)
        ->set('telephone', '90 77 77 77')->call('envoyerCode')
        ->assertSet('etape', 'code')
        ->assertSee('Si ce numéro correspond');

    expect($this->sms->envoyes)->toBeEmpty();
});
