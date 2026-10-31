<?php

namespace App\Services;

use App\Enums\Repas;
use App\Models\Chambre;
use App\Models\Parametre;
use App\Models\TarifRepas;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use InvalidArgumentException;

/**
 * Calcul des nuitées et des montants (RG05, RG06). Montants en entiers FCFA.
 */
class PricingService
{
    public function __construct(private readonly AvailabilityService $disponibilites) {}

    /** RG05 : nuitées = départ − arrivée (jours calendaires) ; un départ après l'heure limite ajoute une nuitée. */
    public function nuitees(CarbonInterface $arrivee, CarbonInterface $depart): int
    {
        $tz = config('uam.timezone');
        $a = CarbonImmutable::instance($arrivee)->setTimezone($tz);
        $d = CarbonImmutable::instance($depart)->setTimezone($tz);

        $nuitees = (int) $a->startOfDay()->diffInDays($d->startOfDay());
        if ($d->greaterThan($d->setTime($this->disponibilites->heureDepart(), 0))) {
            $nuitees++;
        }

        return max(0, $nuitees);
    }

    /** Taux de TVA en pourcentage (paramètre `tva`, sinon configuration). */
    public function tauxTva(): float
    {
        return (float) Parametre::valeur('tva', config('uam.tva_rate'));
    }

    /** TVA arrondie à l'entier supérieur (RG06), calculée en entiers (points de base) pour éviter les erreurs flottantes. */
    public function tva(int $ht): int
    {
        $pointsDeBase = (int) round($this->tauxTva() * 100);

        return intdiv($ht * $pointsDeBase + 9999, 10000);
    }

    /**
     * @param  array<string, int>  $repas  quantités par repas (clé = valeur de l'enum Repas)
     * @return array{nuitees: int, prix_nuit: int, lignes: list<array{libelle: string, repas: ?string, quantite: int, prix_unitaire: int, montant: int}>, montant_ht: int, montant_tva: int, montant_ttc: int}
     */
    public function devis(Chambre $chambre, CarbonInterface $arrivee, CarbonInterface $depart, array $repas = []): array
    {
        $nuitees = $this->nuitees($arrivee, $depart);
        if ($nuitees < 1) {
            throw new InvalidArgumentException('Le séjour doit compter au moins une nuitée.');
        }

        $tarif = $chambre->tarifA(CarbonImmutable::instance($arrivee)->setTimezone(config('uam.timezone')));
        if ($tarif === null) {
            throw new InvalidArgumentException('Aucun tarif en vigueur pour cette chambre à la date d\'arrivée.');
        }

        $lignes = [[
            'libelle' => 'Nuitées',
            'repas' => null,
            'quantite' => $nuitees,
            'prix_unitaire' => $tarif->prix_nuit,
            'montant' => $nuitees * $tarif->prix_nuit,
        ]];

        foreach ($repas as $cle => $quantite) {
            $quantite = (int) $quantite;
            if ($quantite < 1) {
                continue;
            }
            $type = Repas::from($cle);
            $prix = (int) (TarifRepas::query()->where('repas', $type->value)->value('prix') ?? 0);
            $lignes[] = [
                'libelle' => $type->libelle(),
                'repas' => $type->value,
                'quantite' => $quantite,
                'prix_unitaire' => $prix,
                'montant' => $quantite * $prix,
            ];
        }

        $ht = array_sum(array_column($lignes, 'montant'));
        $tva = $this->tva($ht);

        return [
            'nuitees' => $nuitees,
            'prix_nuit' => $tarif->prix_nuit,
            'lignes' => $lignes,
            'montant_ht' => $ht,
            'montant_tva' => $tva,
            'montant_ttc' => $ht + $tva,
        ];
    }
}
