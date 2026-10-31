<?php

use App\Models\Chambre;
use App\Models\Site;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;

beforeEach(fn () => $this->seed(RolesAndPermissionsSeeder::class));

it('redirige un visiteur vers la connexion du back-office', function () {
    $this->get('/admin')->assertRedirect('/admin/login');
});

it('affiche le back-office et les référentiels à l\'administrateur', function () {
    $admin = User::factory()->create()->assignRole(User::ROLE_ADMIN);
    Site::factory()->create(['nom' => 'Site de test']);

    $this->actingAs($admin);

    foreach (['', '/villes', '/sites', '/type-chambres', '/chambres', '/tarifs', '/tarif-repas', '/organismes',
        '/clients', '/reservations', '/paiements', '/factures', '/users', '/parametres', '/audit-logs'] as $chemin) {
        $this->get('/admin'.$chemin)->assertOk();
    }
});

it('interdit la gestion des utilisateurs à un gestionnaire', function () {
    $gestionnaire = User::factory()->create()->assignRole(User::ROLE_GESTIONNAIRE);

    $this->actingAs($gestionnaire)->get('/admin/users')->assertForbidden();
    $this->get('/admin/reservations')->assertOk();
});

it('n\'ouvre pas le back-office à un compte désactivé', function () {
    $inactif = User::factory()->create(['actif' => false])->assignRole(User::ROLE_ADMIN);

    $this->actingAs($inactif)->get('/admin')->assertForbidden();
});

it('affiche les formulaires de site et de chambre avec le téléversement de photos', function () {
    $admin = User::factory()->create()->assignRole(User::ROLE_ADMIN);
    $site = Site::factory()->create();
    $chambre = Chambre::factory()->create(['site_id' => $site->id]);

    $this->actingAs($admin);

    $this->get("/admin/sites/{$site->id}/edit")->assertOk()->assertSee('Ajouter une photo');
    $this->get("/admin/chambres/{$chambre->id}/edit")->assertOk()->assertSee('Ajouter une photo');
    $this->get('/admin/sites/create')->assertOk();
    $this->get('/admin/chambres/create')->assertOk();
});
