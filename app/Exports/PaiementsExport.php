<?php

namespace App\Exports;

use App\Models\Paiement;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

/** Export Excel des paiements (requête déjà restreinte aux sites de l'utilisateur). */
class PaiementsExport implements FromQuery, ShouldAutoSize, WithHeadings, WithMapping
{
    use Exportable;

    /** @param Builder<Paiement> $requete */
    public function __construct(private readonly Builder $requete) {}

    /** @return Builder<Paiement> */
    public function query(): Builder
    {
        return $this->requete->with(['reservation.chambre.site', 'recuPar'])->reorder('paiements.id');
    }

    /** @return list<string> */
    public function headings(): array
    {
        return ['Payé le', 'N° de fiche', 'Site', 'Montant (FCFA)', 'Mode', 'Référence', 'Reçu par'];
    }

    /** @param Paiement $paiement @return list<mixed> */
    public function map($paiement): array
    {
        return [
            $paiement->paye_le->setTimezone(config('uam.timezone'))->format('d/m/Y H:i'),
            $paiement->reservation->numero,
            $paiement->reservation->chambre->site->nom,
            $paiement->montant,
            $paiement->mode->libelle(),
            $paiement->reference,
            $paiement->recuPar?->name,
        ];
    }
}
