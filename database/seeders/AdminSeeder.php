<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class AdminSeeder extends Seeder
{
    /**
     * Administrateur initial. Identifiants lus dans .env (ADMIN_EMAIL, ADMIN_PASSWORD) ;
     * sans mot de passe défini, un mot de passe aléatoire est généré et affiché une seule fois.
     */
    public function run(): void
    {
        $email = config('uam.admin.email');

        if (User::where('email', $email)->exists()) {
            return;
        }

        $motDePasse = config('uam.admin.password');
        $genere = $motDePasse === null || $motDePasse === '';
        if ($genere) {
            $motDePasse = Str::password(16, symbols: false);
        }

        $admin = User::create([
            'name' => 'Administrateur DAIG',
            'email' => $email,
            'password' => $motDePasse,
            'actif' => true,
        ]);
        $admin->assignRole(User::ROLE_ADMIN);

        if ($genere) {
            $this->command?->warn("Administrateur créé : {$email} / {$motDePasse} (à changer dès la première connexion)");
        }
    }
}
