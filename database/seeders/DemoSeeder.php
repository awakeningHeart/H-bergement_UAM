<?php

namespace Database\Seeders;

use App\Enums\Repas;
use App\Models\Chambre;
use App\Models\Organisme;
use App\Models\Site;
use App\Models\Tarif;
use App\Models\TarifRepas;
use App\Models\TypeChambre;
use App\Models\User;
use App\Models\Ville;
use Illuminate\Database\Seeder;

/** Données de démonstration fictives : tout ce qui est institutionnel est marqué [À renseigner]. */
class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $niamey = Ville::firstOrCreate(['slug' => 'niamey'], ['nom' => 'Niamey', 'latitude' => 13.5116, 'longitude' => 2.1254]);
        $ville2 = Ville::firstOrCreate(['slug' => 'ville-2'], ['nom' => '[Ville 2 - À renseigner]', 'latitude' => 13.0500, 'longitude' => 3.2000]);

        $sites = [
            ['ville' => $niamey, 'slug' => 'site-1', 'nom' => '[Site 1 - À renseigner]', 'lat' => 13.5290, 'lng' => 2.0980],
            ['ville' => $niamey, 'slug' => 'site-2', 'nom' => '[Site 2 - À renseigner]', 'lat' => 13.5100, 'lng' => 2.1100],
            ['ville' => $ville2, 'slug' => 'site-3', 'nom' => '[Site 3 - À renseigner]', 'lat' => 13.0450, 'lng' => 3.1950],
        ];

        $simple = TypeChambre::firstOrCreate(['libelle' => 'Chambre simple'], ['capacite' => 1, 'equipements' => ['Climatisation', 'Wi-Fi', 'Douche']]);
        $double = TypeChambre::firstOrCreate(['libelle' => 'Chambre double'], ['capacite' => 2, 'equipements' => ['Climatisation', 'Wi-Fi', 'Douche', 'Télévision']]);
        $suite = TypeChambre::firstOrCreate(['libelle' => 'Suite'], ['capacite' => 3, 'equipements' => ['Climatisation', 'Wi-Fi', 'Douche', 'Télévision', 'Salon']]);

        // Tarifs de démonstration par type (valeurs fictives à remplacer).
        foreach ([[$simple, 15000], [$double, 25000], [$suite, 40000]] as [$type, $prix]) {
            Tarif::firstOrCreate(
                ['type_chambre_id' => $type->id, 'chambre_id' => null],
                ['prix_nuit' => $prix, 'date_debut' => '2026-01-01'],
            );
        }

        foreach ([[Repas::PetitDejeuner, 2000], [Repas::Dejeuner, 4000], [Repas::Diner, 4000]] as [$repas, $prix]) {
            TarifRepas::firstOrCreate(['repas' => $repas], ['prix' => $prix]);
        }

        $sitesCrees = [];
        foreach ($sites as $s) {
            $site = Site::firstOrCreate(['slug' => $s['slug']], [
                'ville_id' => $s['ville']->id,
                'nom' => $s['nom'],
                'adresse' => '[À renseigner]',
                'latitude' => $s['lat'],
                'longitude' => $s['lng'],
                'description' => '[À renseigner] Description du site.',
                'telephone' => null,
                'actif' => true,
            ]);
            foreach ([['01', $simple], ['02', $simple], ['03', $double], ['04', $double], ['05', $suite]] as [$numero, $type]) {
                Chambre::firstOrCreate(['site_id' => $site->id, 'numero' => $numero], [
                    'type_chambre_id' => $type->id,
                    'etage' => $numero <= '02' ? '0' : '1',
                    'description' => '[À renseigner]',
                    'actif' => true,
                ]);
            }
            $sitesCrees[] = $site;
        }

        Organisme::firstOrCreate(['nom' => '[Organisme de démonstration - À renseigner]'], ['contact' => '[À renseigner]']);

        // Comptes de démonstration (mot de passe aléatoire : à réinitialiser par l'admin).
        $gestionnaire = User::firstOrCreate(['email' => 'gestionnaire@uam.local'], [
            'name' => 'Gestionnaire démo', 'password' => bin2hex(random_bytes(12)), 'actif' => true,
        ]);
        $gestionnaire->assignRole(User::ROLE_GESTIONNAIRE);
        $gestionnaire->sites()->syncWithoutDetaching([$sitesCrees[0]->id]);

        $direction = User::firstOrCreate(['email' => 'direction@uam.local'], [
            'name' => 'Direction démo', 'password' => bin2hex(random_bytes(12)), 'actif' => true,
        ]);
        $direction->assignRole(User::ROLE_DIRECTION);
    }
}
