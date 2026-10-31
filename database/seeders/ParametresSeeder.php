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
            'facturation_adresse' => '[À renseigner]',
            'facturation_telephone' => '[À renseigner]',
            'facturation_email' => '[À renseigner]',
            'facturation_nif' => '[À renseigner]',
            'banque_nom' => '[À renseigner]',
            'banque_iban' => '[À renseigner]',
            'conditions_generales' => '[À renseigner] Conditions générales de séjour et de réservation.',
        ];

        foreach ($parametres as $cle => $valeur) {
            Parametre::firstOrCreate(['cle' => $cle], ['valeur' => $valeur]);
        }
    }
}
