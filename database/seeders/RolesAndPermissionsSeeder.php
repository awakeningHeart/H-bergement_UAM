<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    public const PERMISSIONS = [
        'referentiels.voir',
        'referentiels.gerer',
        'reservations.voir',
        'reservations.gerer',
        'paiements.gerer',
        'factures.gerer',
        'clients.voir',
        'clients.gerer',
        'utilisateurs.gerer',
        'parametres.gerer',
        'audit.voir',
        'exports.generer',
    ];

    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (self::PERMISSIONS as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        Role::findOrCreate(User::ROLE_ADMIN, 'web')->syncPermissions(self::PERMISSIONS);

        // Réceptionniste / gestionnaire de site : limité à ses sites par les Policies (RG11).
        Role::findOrCreate(User::ROLE_GESTIONNAIRE, 'web')->syncPermissions([
            'referentiels.voir',
            'reservations.voir', 'reservations.gerer',
            'paiements.gerer', 'factures.gerer',
            'clients.voir', 'clients.gerer',
            'exports.generer',
        ]);

        // Direction / Rectorat : lecture seule.
        Role::findOrCreate(User::ROLE_DIRECTION, 'web')->syncPermissions([
            'referentiels.voir', 'reservations.voir', 'clients.voir', 'audit.voir', 'exports.generer',
        ]);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
