<?php

namespace App\Services;

use App\Enums\StatutReservation;
use App\Models\Chambre;
use App\Models\Paiement;
use App\Models\Reservation;
use App\Models\User;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;

/**
 * Indicateurs du tableau de bord, limités aux sites accessibles à l'utilisateur (RG11).
 * Les périodes s'entendent en heure de Niamey.
 */
class StatistiquesService
{
    public function __construct(private readonly User $utilisateur) {}

    private function maintenant(): CarbonImmutable
    {
        return CarbonImmutable::now(config('uam.timezone'));
    }

    public function enAttente(): int
    {
        return $this->reservations()->where('statut', StatutReservation::EnAttente->value)->count();
    }

    public function arriveesDuJour(): int
    {
        [$debut, $fin] = $this->bornesUtc($this->maintenant()->startOfDay(), $this->maintenant()->endOfDay());

        return $this->reservations()->where('statut', StatutReservation::Confirmee->value)
            ->whereBetween('arrivee', [$debut, $fin])->count();
    }

    public function departsDuJour(): int
    {
        [$debut, $fin] = $this->bornesUtc($this->maintenant()->startOfDay(), $this->maintenant()->endOfDay());

        return $this->reservations()->where('statut', StatutReservation::EnCours->value)
            ->whereBetween('depart', [$debut, $fin])->count();
    }

    /**
     * Taux d'occupation (%) : nuitées occupées / (chambres actives × nuits) sur [début, fin[.
     * Comptent les réservations confirmées, en cours et terminées.
     */
    public function tauxOccupation(CarbonInterface $debut, CarbonInterface $fin): float
    {
        $tz = config('uam.timezone');
        $debut = CarbonImmutable::instance($debut)->setTimezone($tz)->startOfDay();
        $fin = CarbonImmutable::instance($fin)->setTimezone($tz)->startOfDay();
        $jours = (int) $debut->diffInDays($fin);
        $chambres = Chambre::query()->accessiblesPar($this->utilisateur)->where('actif', true)->count();
        if ($jours < 1 || $chambres < 1) {
            return 0.0;
        }

        $statuts = [StatutReservation::Confirmee->value, StatutReservation::EnCours->value, StatutReservation::Terminee->value];
        [$debutUtc, $finUtc] = $this->bornesUtc($debut, $fin);
        $occupees = 0;

        $this->reservations()->whereIn('statut', $statuts)
            ->where('arrivee', '<', $finUtc)->where('depart', '>', $debutUtc)
            ->get(['arrivee', 'depart'])
            ->each(function (Reservation $r) use ($tz, $debut, $fin, &$occupees) {
                $a = CarbonImmutable::instance($r->arrivee)->setTimezone($tz)->startOfDay();
                $d = CarbonImmutable::instance($r->depart)->setTimezone($tz)->startOfDay();
                $de = $a->greaterThan($debut) ? $a : $debut;
                $a2 = $d->lessThan($fin) ? $d : $fin;
                $occupees += max(0, (int) $de->diffInDays($a2));
            });

        return round(min(100, $occupees / ($chambres * $jours) * 100), 1);
    }

    /** @return array<string, int> libellé de statut => nombre de réservations arrivant ce mois-ci */
    public function parStatutDuMois(): array
    {
        [$debut, $fin] = $this->bornesUtc($this->maintenant()->startOfMonth(), $this->maintenant()->endOfMonth());
        $comptes = $this->reservations()->whereBetween('arrivee', [$debut, $fin])
            ->selectRaw('statut, count(*) as total')->groupBy('statut')->pluck('total', 'statut');

        $resultat = [];
        foreach (StatutReservation::cases() as $statut) {
            $resultat[$statut->libelle()] = (int) ($comptes[$statut->value] ?? 0);
        }

        return $resultat;
    }

    /** @return array<string, int> nom du site => réservations actives ou terminées arrivant ce mois-ci */
    public function parSiteDuMois(): array
    {
        [$debut, $fin] = $this->bornesUtc($this->maintenant()->startOfMonth(), $this->maintenant()->endOfMonth());
        $exclus = [StatutReservation::Refusee->value, StatutReservation::Expiree->value, StatutReservation::Annulee->value];

        return $this->reservations()->whereBetween('arrivee', [$debut, $fin])->whereNotIn('statut', $exclus)
            ->with('chambre.site')->get()
            ->groupBy(fn (Reservation $r) => $r->chambre->site->nom)
            ->map->count()->sortDesc()->all();
    }

    /** @return array<string, int> « mm/aaaa » => paiements encaissés (FCFA) sur les 6 derniers mois */
    public function revenusMensuels(int $mois = 6): array
    {
        $tz = config('uam.timezone');
        $maintenant = $this->maintenant();
        $resultat = [];
        for ($i = $mois - 1; $i >= 0; $i--) {
            $resultat[$maintenant->startOfMonth()->subMonths($i)->format('m/Y')] = 0;
        }

        $premier = $maintenant->startOfMonth()->subMonths($mois - 1)->utc();
        Paiement::query()->accessiblesPar($this->utilisateur)->where('paye_le', '>=', $premier)->get(['montant', 'paye_le'])
            ->each(function (Paiement $p) use ($tz, &$resultat) {
                $cle = CarbonImmutable::instance($p->paye_le)->setTimezone($tz)->format('m/Y');
                if (array_key_exists($cle, $resultat)) {
                    $resultat[$cle] += $p->montant;
                }
            });

        return $resultat;
    }

    public function revenusDuMois(): int
    {
        return array_values($this->revenusMensuels(1))[0];
    }

    /** Montant restant dû sur les séjours en cours ou terminés (en FCFA). */
    public function impayes(): int
    {
        return $this->reservations()->whereIn('statut', [StatutReservation::EnCours->value, StatutReservation::Terminee->value])
            ->get()->sum(fn (Reservation $r) => max(0, $r->soldeDu()));
    }

    /** @return Builder<Reservation> */
    private function reservations()
    {
        return Reservation::query()->accessiblesPar($this->utilisateur);
    }

    /** @return array{0: CarbonImmutable, 1: CarbonImmutable} */
    private function bornesUtc(CarbonInterface $debut, CarbonInterface $fin): array
    {
        return [CarbonImmutable::instance($debut)->utc(), CarbonImmutable::instance($fin)->utc()];
    }
}
