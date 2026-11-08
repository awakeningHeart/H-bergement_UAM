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

/**
 * Données de démonstration (reprises du design de référence) : à valider et remplacer par l'UAM.
 * Les informations institutionnelles non connues sont marquées [À renseigner].
 */
class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $villes = [];
        foreach ([
            ['niamey', 'Niamey', 13.5116, 2.1254],
            ['dosso', 'Dosso', 13.0490, 3.1937],
            ['tillaberi', 'Tillabéri', 14.2111, 1.4536],
            ['say', 'Say', 13.1000, 2.3667],
        ] as [$slug, $nom, $lat, $lng]) {
            $villes[$slug] = Ville::firstOrCreate(['slug' => $slug], ['nom' => $nom, 'latitude' => $lat, 'longitude' => $lng]);
        }

        $simple = TypeChambre::firstOrCreate(['libelle' => 'Chambre Simple'], ['capacite' => 1, 'equipements' => ['Climatisation', 'Salle de bain privée', 'Wi-Fi']]);
        $double = TypeChambre::firstOrCreate(['libelle' => 'Chambre Double Supérieure'], ['capacite' => 2, 'equipements' => ['Climatisation', 'Salle de bain privée', 'Bureau']]);
        $suite = TypeChambre::firstOrCreate(['libelle' => 'Suite Rectorat'], ['capacite' => 2, 'equipements' => ['Salon privé', 'Climatisation', 'Terrasse']]);

        // Versions anglaises de démonstration (interface anglaise), sans écraser une saisie existante.
        foreach ([
            [$simple, 'Single Room', ['Air conditioning', 'Private bathroom', 'Wi-Fi']],
            [$double, 'Superior Double Room', ['Air conditioning', 'Private bathroom', 'Desk']],
            [$suite, 'Rectorate Suite', ['Private lounge', 'Air conditioning', 'Terrace']],
        ] as [$type, $libelle, $equipements]) {
            if (blank($type->libelle_en)) {
                $type->update(['libelle_en' => $libelle, 'equipements_en' => $equipements]);
            }
        }

        // Tarifs de démonstration par type (valeurs du design, à remplacer par les tarifs officiels).
        foreach ([[$simple, 18000], [$double, 25000], [$suite, 40000]] as [$type, $prix]) {
            Tarif::firstOrCreate(
                ['type_chambre_id' => $type->id, 'chambre_id' => null],
                ['prix_nuit' => $prix, 'date_debut' => '2026-01-01'],
            );
        }

        foreach ([[Repas::PetitDejeuner, 2500], [Repas::Dejeuner, 5000], [Repas::Diner, 5000]] as [$repas, $prix]) {
            TarifRepas::firstOrCreate(['repas' => $repas], ['prix' => $prix]);
        }

        $sites = [
            ['niamey', 'maison-hotes-campus', "Maison d'hôtes du Campus", 'Campus universitaire, rive droite', 'University campus, right bank', 13.4997, 2.0946],
            ['niamey', 'residence-rectorat', 'Résidence du Rectorat', 'Rive droite, près du Rectorat', 'Right bank, near the Rectorate', 13.5045, 2.1012],
            ['niamey', 'villa-des-hotes', 'Villa des Hôtes', 'Quartier Plateau', 'Plateau district', 13.5232, 2.1058],
            ['dosso', 'dosso-site-regional', '[Site régional de Dosso - À renseigner]', '[À renseigner]', null, 13.0490, 3.1937],
            ['tillaberi', 'tillaberi-site-regional', '[Site régional de Tillabéri - À renseigner]', '[À renseigner]', null, 14.2111, 1.4536],
            ['say', 'say-site-regional', '[Site régional de Say - À renseigner]', '[À renseigner]', null, 13.1000, 2.3667],
        ];

        $sitesCrees = [];
        foreach ($sites as [$ville, $slug, $nom, $adresse, $adresseEn, $lat, $lng]) {
            $site = Site::firstOrCreate(['slug' => $slug], [
                'ville_id' => $villes[$ville]->id,
                'nom' => $nom,
                'adresse' => $adresse,
                'latitude' => $lat,
                'longitude' => $lng,
                'description' => '[À renseigner] Description du site.',
                'telephone' => null,
                'actif' => true,
            ]);
            if ($adresseEn && blank($site->adresse_en)) {
                $site->update(['adresse_en' => $adresseEn]);
            }
            foreach ([['101', $simple, '1'], ['102', $simple, '1'], ['201', $double, '2'], ['202', $double, '2'], ['301', $suite, '3']] as [$numero, $type, $etage]) {
                Chambre::firstOrCreate(['site_id' => $site->id, 'numero' => $numero], [
                    'type_chambre_id' => $type->id,
                    'etage' => $etage,
                    'description' => '[À renseigner]',
                    'actif' => true,
                ]);
            }
            $sitesCrees[] = $site;
        }

        Organisme::firstOrCreate(['nom' => "Ministère de l'Enseignement supérieur"], ['contact' => '[À renseigner]']);
        Organisme::firstOrCreate(['nom' => 'Projet partenaire'], ['contact' => '[À renseigner]']);
        Organisme::firstOrCreate(['nom' => 'Université partenaire'], ['contact' => '[À renseigner]']);

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
