<?php

namespace Database\Seeders;

use App\Enums\ModePaiement;
use App\Enums\Motif;
use App\Enums\Payeur;
use App\Enums\StatutReservation;
use App\Models\Chambre;
use App\Models\Client;
use App\Models\Organisme;
use App\Models\Paiement;
use App\Models\Reservation;
use App\Models\Sequence;
use App\Services\FactureService;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/** Réservations fictives couvrant tous les statuts, pour la démonstration du tableau de bord et du workflow. */
class DemoReservationsSeeder extends Seeder
{
    public function run(): void
    {
        if (Reservation::query()->exists()) {
            return;
        }

        $tz = config('uam.timezone');
        $maintenant = CarbonImmutable::now($tz);
        $chambres = Chambre::query()->with('type')->orderBy('id')->get();
        $organisme = Organisme::query()->first();
        $annee = (int) $maintenant->year;

        $clients = collect([
            ['+22796000001', 'Moussa', 'Aïchatou'], ['+22796000002', 'Issoufou', 'Mahamadou'], ['+22796000003', 'Garba', 'Fatouma'],
            ['+22796000004', 'Hamidou', 'Zeinabou'], ['+22796000005', 'Souley', 'Abdou'], ['+22796000006', 'Amadou', 'Rakia'],
        ])->map(fn ($c) => Client::firstOrCreate(['telephone' => $c[0]], [
            'nom' => $c[1], 'prenom' => $c[2], 'type_piece' => 'Passeport', 'numero_piece' => strtoupper(Str::random(8)),
            'email' => strtolower($c[2]).'@exemple.ne',
        ]));

        // decalage : jours d'écart avec aujourd'hui ; organisme : facturé à l'organisme ; paye : % réglé
        $plan = [
            ['decalage' => 3, 'nuitees' => 2, 'statut' => StatutReservation::EnAttente, 'organisme' => false, 'paye' => 0],
            ['decalage' => 6, 'nuitees' => 3, 'statut' => StatutReservation::EnAttente, 'organisme' => true, 'paye' => 0],
            ['decalage' => 0, 'nuitees' => 2, 'statut' => StatutReservation::Confirmee, 'organisme' => false, 'paye' => 0],      // arrive aujourd'hui
            ['decalage' => 8, 'nuitees' => 4, 'statut' => StatutReservation::Confirmee, 'organisme' => false, 'paye' => 0],
            ['decalage' => -1, 'nuitees' => 3, 'statut' => StatutReservation::EnCours, 'organisme' => false, 'paye' => 50],      // paiement partiel
            ['decalage' => -9, 'nuitees' => 2, 'statut' => StatutReservation::Terminee, 'organisme' => false, 'paye' => 100],
            ['decalage' => -33, 'nuitees' => 4, 'statut' => StatutReservation::Terminee, 'organisme' => false, 'paye' => 100],
            ['decalage' => -65, 'nuitees' => 3, 'statut' => StatutReservation::Terminee, 'organisme' => true, 'paye' => 100],
            ['decalage' => -95, 'nuitees' => 2, 'statut' => StatutReservation::Terminee, 'organisme' => false, 'paye' => 100],
            ['decalage' => 5, 'nuitees' => 2, 'statut' => StatutReservation::Annulee, 'organisme' => false, 'paye' => 0],
            ['decalage' => 4, 'nuitees' => 2, 'statut' => StatutReservation::Refusee, 'organisme' => false, 'paye' => 0],
            ['decalage' => -3, 'nuitees' => 2, 'statut' => StatutReservation::NonPresente, 'organisme' => false, 'paye' => 0],
            ['decalage' => -5, 'nuitees' => 2, 'statut' => StatutReservation::Expiree, 'organisme' => false, 'paye' => 0],
        ];

        $sejourEffectif = fn (StatutReservation $statut): bool => in_array($statut, [StatutReservation::EnCours, StatutReservation::Terminee], true);

        foreach ($plan as $i => $ligne) {
            ['decalage' => $decalage, 'nuitees' => $nuitees, 'statut' => $statut, 'organisme' => $parOrganisme, 'paye' => $pourcentPaye] = $ligne;
            $chambre = $chambres[$i % $chambres->count()];
            $tarif = $chambre->tarifA($maintenant->addDays($decalage));
            if ($tarif === null) {
                continue;
            }

            $arrivee = $maintenant->addDays($decalage)->setTime(14, 0);
            $depart = $arrivee->addDays($nuitees)->setTime(12, 0);
            $ht = $nuitees * $tarif->prix_nuit;
            $tva = intdiv($ht * (int) round((float) config('uam.tva_rate') * 100) + 9999, 10000);

            $reservation = Reservation::query()->create([
                'numero' => sprintf('UAM-MH-%d-%05d', $annee, Sequence::suivante('fiche', $annee)),
                'client_id' => $clients[$i % $clients->count()]->id,
                'chambre_id' => $chambre->id,
                'organisme_id' => $parOrganisme ? $organisme?->id : null,
                'motif' => [Motif::Mission, Motif::Colloque, Motif::Jury, Motif::EnseignantVisiteur][$i % 4],
                'arrivee' => $arrivee->utc(),
                'depart' => $depart->utc(),
                'nuitees' => $nuitees,
                'correspondant_nom' => 'Service accueil',
                'payeur' => $parOrganisme && $organisme ? Payeur::Organisme : Payeur::Client,
                'montant_ht' => $ht,
                'montant_tva' => $tva,
                'montant_ttc' => $ht + $tva,
                'statut' => $statut,
                'jeton_suivi_hash' => hash('sha256', Str::random(40)),
                'signature' => 'Démonstration',
                'conditions_acceptees_le' => $arrivee->subDays(2)->utc(),
                'cles_remises_le' => $sejourEffectif($statut) ? $arrivee->utc() : null,
                'depart_effectif_le' => $statut === StatutReservation::Terminee ? $depart->utc() : null,
            ]);
            $reservation->forceFill(['created_at' => $arrivee->subDays(4)->utc(), 'updated_at' => $arrivee->subDays(4)->utc()])->saveQuietly();

            if ($sejourEffectif($statut)) {
                $facture = app(FactureService::class)->emettre($reservation);
                if ($pourcentPaye > 0) {
                    Paiement::query()->create([
                        'reservation_id' => $reservation->id,
                        'facture_id' => $facture->id,
                        'montant' => intdiv($reservation->montant_ttc * $pourcentPaye, 100),
                        'mode' => [ModePaiement::Especes, ModePaiement::Virement, ModePaiement::MobileMoney][$i % 3],
                        'reference' => $i % 3 === 1 ? 'VIR-DEMO-'.$i : null,
                        'paye_le' => $arrivee->addDay()->utc(),
                    ]);
                }
            }
        }
    }
}
