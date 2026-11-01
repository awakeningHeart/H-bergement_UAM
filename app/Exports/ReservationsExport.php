<?php

namespace App\Exports;

use App\Models\Reservation;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

/** Export Excel des réservations. La requête reçue est déjà restreinte aux sites de l'utilisateur (RG11). */
class ReservationsExport implements FromQuery, ShouldAutoSize, WithHeadings, WithMapping
{
    use Exportable;

    /** @param Builder<Reservation> $requete */
    public function __construct(private readonly Builder $requete) {}

    /** @return Builder<Reservation> */
    public function query(): Builder
    {
        return $this->requete->with(['client', 'chambre.site.ville', 'chambre.type', 'organisme'])->reorder('reservations.id');
    }

    /** @return list<string> */
    public function headings(): array
    {
        return ['N° de fiche', 'Statut', 'Ville', 'Site', 'Chambre', 'Type', 'Client', 'Téléphone', 'Motif', 'Arrivée', 'Départ',
            'Nuitées', 'Payeur', 'Organisme', 'HT (FCFA)', 'TVA (FCFA)', 'TTC (FCFA)', 'Créée le'];
    }

    /** @param Reservation $reservation @return list<mixed> */
    public function map($reservation): array
    {
        $tz = config('uam.timezone');

        return [
            $reservation->numero,
            $reservation->statut->libelle(),
            $reservation->chambre->site->ville->nom,
            $reservation->chambre->site->nom,
            $reservation->chambre->numero,
            $reservation->chambre->type->libelle,
            $reservation->client->nomComplet(),
            $reservation->client->telephone,
            $reservation->motif->libelle(),
            $reservation->arrivee->setTimezone($tz)->format('d/m/Y H:i'),
            $reservation->depart->setTimezone($tz)->format('d/m/Y H:i'),
            $reservation->nuitees,
            $reservation->payeur->libelle(),
            $reservation->organisme?->nom,
            $reservation->montant_ht,
            $reservation->montant_tva,
            $reservation->montant_ttc,
            $reservation->created_at?->setTimezone($tz)->format('d/m/Y H:i'),
        ];
    }
}
