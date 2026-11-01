<?php

namespace App\Services;

use App\Enums\ModePaiement;
use App\Enums\StatutReservation;
use App\Exceptions\ReservationException;
use App\Models\Paiement;
use App\Models\Reservation;
use App\Models\User;
use App\Support\Format;
use Illuminate\Support\Facades\DB;

class PaiementService
{
    /**
     * Enregistre un paiement (espèces, virement, chèque, mobile money).
     * Le montant ne peut pas dépasser le solde dû (en entiers FCFA).
     *
     * @throws ReservationException
     */
    public function enregistrer(Reservation $reservation, int $montant, ModePaiement $mode, ?string $reference, ?User $acteur): Paiement
    {
        if ($acteur !== null && (! $acteur->can('paiements.gerer') || ! $acteur->can('update', $reservation))) {
            throw new ReservationException(__('reservation.workflow.interdit'));
        }
        if ($montant < 1) {
            throw new ReservationException(__('reservation.paiements.montant_invalide'));
        }

        return DB::transaction(function () use ($reservation, $montant, $mode, $reference, $acteur) {
            $reservation = Reservation::query()->whereKey($reservation->id)->lockForUpdate()->firstOrFail();

            if (! in_array($reservation->statut, [StatutReservation::Confirmee, StatutReservation::EnCours, StatutReservation::Terminee], true)) {
                throw new ReservationException(__('reservation.paiements.statut_invalide'));
            }
            if ($montant > $reservation->soldeDu()) {
                throw new ReservationException(__('reservation.paiements.depasse_solde', ['solde' => Format::fcfa(max(0, $reservation->soldeDu()))]));
            }

            return Paiement::query()->create([
                'reservation_id' => $reservation->id,
                'facture_id' => $reservation->factures()->where('type', 'facture')->whereDoesntHave('avoirs')->value('id'),
                'montant' => $montant,
                'mode' => $mode,
                'reference' => $reference,
                'recu_par' => $acteur?->id,
                'paye_le' => now(),
            ]);
        });
    }
}
