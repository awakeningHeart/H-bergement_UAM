<?php

use App\Enums\StatutReservation;
use App\Http\Middleware\DefinirLangue;
use App\Livewire\ReservationModal;
use App\Models\Chambre;
use App\Models\Site;
use App\Models\Tarif;
use App\Models\User;
use App\Models\Ville;
use Database\Seeders\RolesAndPermissionsSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->ville = Ville::factory()->create(['nom' => 'Niamey', 'slug' => 'niamey']);
    $this->site = Site::factory()->create(['ville_id' => $this->ville->id, 'nom' => 'Villa test', 'slug' => 'villa-test']);
    $this->chambre = Chambre::factory()->create(['site_id' => $this->site->id, 'numero' => '104']);
    Tarif::factory()->create(['type_chambre_id' => $this->chambre->type_chambre_id, 'prix_nuit' => 18000, 'date_debut' => '2026-01-01']);
});

it('affiche le site en français par défaut avec un lien vers l\'anglais', function () {
    $this->get('/')
        ->assertOk()
        ->assertSee('<html lang="fr">', false)
        ->assertSee('Rechercher')
        ->assertSee(route('langue', 'en'), false);
});

it('passe le site en anglais et revient à la page d\'origine', function () {
    $page = route('ville', $this->ville);

    $this->from($page)->get(route('langue', 'en'))
        ->assertRedirect($page)
        ->assertSessionHas('langue', 'en');

    $this->get('/')
        ->assertOk()
        ->assertSee('<html lang="en">', false)
        ->assertSee('Search')
        ->assertSee('Book this room')
        ->assertSee('Room 104')
        ->assertSee(route('langue', 'fr'), false);

    $this->get(route('langue', 'fr'));
    $this->get('/')->assertSee('<html lang="fr">', false)->assertSee('Rechercher');
});

it('refuse une langue non prise en charge', function () {
    $this->get('/langue/de')->assertNotFound();
});

it('ne redirige pas vers un autre domaine après le changement de langue', function () {
    $this->withHeader('Referer', 'https://exemple.com/piege')->get(route('langue', 'en'))
        ->assertRedirect(route('accueil'));
    $this->withHeader('Referer', rtrim(url('/'), '/').'.exemple.com/piege')->get(route('langue', 'en'))
        ->assertRedirect(route('accueil'));
});

it('garde le back-office en français même si le visiteur a choisi l\'anglais', function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $admin = User::factory()->create()->assignRole(User::ROLE_ADMIN);

    $this->actingAs($admin)->withSession(['langue' => 'en'])->get('/admin/reservations')
        ->assertOk()
        ->assertSee('lang="fr"', false)
        ->assertSee('Réservations');
});

it('affiche le formulaire de réservation et ses erreurs en anglais', function () {
    app()->setLocale('en');

    $modal = Livewire::test(ReservationModal::class)
        ->dispatch('reservation-ouvrir', chambre: $this->chambre->id,
            arrivee: now('Africa/Niamey')->addDays(10)->toDateString(), depart: now('Africa/Niamey')->addDays(12)->toDateString())
        ->assertSee('Change room')
        ->assertSee('Phone and identity')
        ->set('telephone', '90 12 34 56')->call('verifierTelephone')
        ->assertSee('Passport')
        ->call('continuer')
        ->assertHasErrors(['nom']);

    expect($modal->errors()->first('nom'))->toBe('The last name field is required.');
});

it('garde le formulaire dans la langue de la page lors des actions Livewire', function () {
    expect(Livewire::getPersistentMiddleware())->toContain(DefinirLangue::class);
});

it('traduit les libellés publics mais garde le journal d\'audit en français', function () {
    app()->setLocale('en');

    expect(StatutReservation::Confirmee->libelle())->toBe('Confirmed')
        ->and(StatutReservation::Confirmee->libelle('fr'))->toBe('Confirmée')
        ->and(__('reservation.workflow.journal', ['de' => 'a', 'vers' => 'b'], 'fr'))->toBe('Statut : a → b');
});
