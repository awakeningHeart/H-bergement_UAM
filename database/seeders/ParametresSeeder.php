<?php

namespace Database\Seeders;

use App\Models\Parametre;
use Illuminate\Database\Seeder;

class ParametresSeeder extends Seeder
{
    public function run(): void
    {
        $parametres = [
            'etablissement_nom' => 'Hébergement UAM - Maisons d\'hôtes de l\'Université Abdou Moumouni',
            'tva' => (float) config('uam.tva_rate'),
            'heure_depart' => config('uam.checkout_hour'),
            'duree_hold_minutes' => config('uam.hold_ttl_minutes'),
            'expiration_attente_heures' => config('uam.pending_expiry_hours'),
            'delai_paiement_heures' => config('uam.payment_deadline_hours'),
            'facturation_adresse' => 'BP 10896/237 Niamey, Niger',
            'facturation_telephone' => '+227 20 31 55 31',
            'facturation_email' => 'rectorat@uam.edu.ne',
            'facturation_nif' => '5713',
            'banque_nom' => '[À renseigner]',
            'banque_iban' => '[À renseigner]',
            // Chemins (disque public) des visuels de la page d'accueil ; vides = fond par défaut.
            'image_accueil' => '',
            'image_experience' => '',
            'conditions_generales' => '[À renseigner] Conditions générales de séjour et de réservation.',
        ];

        foreach ($parametres as $cle => $valeur) {
            Parametre::firstOrCreate(['cle' => $cle], ['valeur' => $valeur]);
        }
    }
}
