<?php

use App\Models\Chambre;
use App\Models\Photo;
use App\Models\Reservation;
use App\Models\Site;
use App\Models\Tarif;
use App\Models\Ville;
use Carbon\CarbonImmutable;

beforeEach(function () {
    $this->ville = Ville::factory()->create(['nom' => 'Niamey', 'slug' => 'niamey']);
    $this->site = Site::factory()->create(['ville_id' => $this->ville->id, 'nom' => 'Villa test', 'slug' => 'villa-test']);
    $this->chambre = Chambre::factory()->create(['site_id' => $this->site->id, 'numero' => '104']);
    Tarif::factory()->create(['type_chambre_id' => $this->chambre->type_chambre_id, 'prix_nuit' => 18000]);
    $this->arrivee = now('Africa/Niamey')->addDays(10)->toDateString();
    $this->depart = now('Africa/Niamey')->addDays(12)->toDateString();
});

it('affiche l\'accueil avec les chambres disponibles et leur prix', function () {
    $this->get('/?'.http_build_query(['arrivee' => $this->arrivee, 'depart' => $this->depart]))
        ->assertOk()
        ->assertSee('Villa test')
        ->assertSee('Ch. 104')
        ->assertSee('18 000', false)
        ->assertSee('Niamey');
});

it('masque une chambre déjà réservée sur la période', function () {
    Reservation::factory()->create([
        'chambre_id' => $this->chambre->id,
        'arrivee' => CarbonImmutable::parse($this->arrivee.' 14:00', 'Africa/Niamey')->utc(),
        'depart' => CarbonImmutable::parse($this->depart.' 10:00', 'Africa/Niamey')->utc(),
    ]);

    $this->get('/?'.http_build_query(['arrivee' => $this->arrivee, 'depart' => $this->depart]))
        ->assertOk()
        ->assertDontSee('Ch. 104')
        ->assertSee('Aucune chambre disponible');
});

it('valide les dates de recherche', function () {
    $this->get('/?arrivee=2020-01-01&depart=2020-01-03')->assertSessionHasErrors('arrivee');
    $this->get('/?'.http_build_query(['arrivee' => $this->depart, 'depart' => $this->arrivee]))->assertSessionHasErrors('depart');
    $this->get('/?personnes=99')->assertSessionHasErrors('personnes');
    $this->get('/?ville=inexistante')->assertSessionHasErrors('ville');
});

it('affiche les pages ville, site et chambre', function () {
    $this->get(route('ville', $this->ville))->assertOk()->assertSee('Villa test');
    $this->get(route('site', $this->site))->assertOk()->assertSee('Villa test')->assertSee('Ch. 104');
    $this->get(route('chambre', $this->chambre))->assertOk()->assertSee('Ch. 104')->assertSee('18 000', false);
});

it('indique l\'indisponibilité sur la fiche chambre et bloque la réservation', function () {
    Reservation::factory()->create([
        'chambre_id' => $this->chambre->id,
        'arrivee' => CarbonImmutable::parse($this->arrivee.' 14:00', 'Africa/Niamey')->utc(),
        'depart' => CarbonImmutable::parse($this->depart.' 10:00', 'Africa/Niamey')->utc(),
    ]);

    $this->get(route('chambre', ['chambre' => $this->chambre, 'arrivee' => $this->arrivee, 'depart' => $this->depart]))
        ->assertOk()
        ->assertSee("n'est pas disponible");
});

it('renvoie 404 pour un site ou une chambre inactifs ou inconnus', function () {
    $inactif = Site::factory()->create(['actif' => false, 'slug' => 'ferme']);
    $chambreInactive = Chambre::factory()->create(['actif' => false]);

    $this->get(route('site', $inactif))->assertNotFound();
    $this->get(route('chambre', $chambreInactive))->assertNotFound();
    $this->get('/chambres/999999')->assertNotFound();
    $this->get('/villes/inconnue')->assertNotFound();
});

it('affiche les photos téléversées', function () {
    Photo::create(['photoable_type' => Chambre::class, 'photoable_id' => $this->chambre->id, 'chemin' => 'photos/test.jpg', 'ordre' => 0]);

    $this->get(route('chambre', $this->chambre))->assertOk()->assertSee('storage/photos/test.jpg', false);
});

it('échappe les données dans la carte des sites', function () {
    $this->site->update(['nom' => 'Maison d\'hôtes "Test" <b>']);

    $reponse = $this->get(route('site', $this->site))->assertOk();
    $reponse->assertSee('&lt;b&gt;', false)->assertDontSee('Test" <b>', false);
});

it('expose l\'API des sites par ville sans donnée personnelle', function () {
    $this->getJson('/api/sites?ville=niamey')
        ->assertOk()
        ->assertJsonPath('data.0.slug', 'villa-test')
        ->assertJsonPath('data.0.chambres', 1)
        ->assertJsonMissingPath('data.0.id');

    $this->getJson('/api/sites?ville=inconnue')->assertUnprocessable();
});

it('expose l\'API des disponibilités', function () {
    $reponse = $this->getJson('/api/chambres/disponibles?'.http_build_query([
        'site' => 'villa-test', 'arrivee' => $this->arrivee, 'depart' => $this->depart,
    ]))->assertOk();

    $reponse->assertJsonPath('data.0.numero', '104')
        ->assertJsonPath('data.0.prix_nuit', 18000)
        ->assertJsonPath('data.0.site', 'Villa test');

    Reservation::factory()->create([
        'chambre_id' => $this->chambre->id,
        'arrivee' => CarbonImmutable::parse($this->arrivee.' 14:00', 'Africa/Niamey')->utc(),
        'depart' => CarbonImmutable::parse($this->depart.' 10:00', 'Africa/Niamey')->utc(),
    ]);

    $this->getJson('/api/chambres/disponibles?'.http_build_query([
        'site' => 'villa-test', 'arrivee' => $this->arrivee, 'depart' => $this->depart,
    ]))->assertOk()->assertJsonCount(0, 'data');
});

it('limite le débit de l\'API publique', function () {
    foreach (range(1, 60) as $i) {
        $this->getJson('/api/sites')->assertOk();
    }
    $this->getJson('/api/sites')->assertStatus(429);
});
