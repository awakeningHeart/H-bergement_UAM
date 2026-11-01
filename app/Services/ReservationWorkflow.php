<?php

namespace App\Services;

use App\Enums\StatutReservation;
use App\Exceptions\ReservationException;
use App\Jobs\EnvoyerSms;
use App\Models\Parametre;
use App\Models\Reservation;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * Workflow des statuts (docs/02-regles-de-gestion.md) : chaque transition est contrôlée,
 * journalisée, et notifiée par SMS lorsque c'est pertinent.
 */
class ReservationWorkflow
{
    public function __construct(private readonly FactureService $factures) {}

    /**
     * @param  User|null  $acteur  null pour une action du système (planificateur) ou du client
     * @param  bool  $notifier  false pour une annulation demandée par le client lui-même
     *
     * @throws ReservationException transition interdite
     */
    public function transitionner(Reservation $reservation, StatutReservation $cible, ?User $acteur = null, ?string $motif = null, bool $notifier = true): Reservation
    {
        if ($acteur !== null && ! $acteur->can('update', $reservation)) {
            throw new ReservationException(__('reservation.workflow.interdit'));
        }

        DB::transaction(function () use (&$reservation, $cible, $acteur, $motif) {
            $reservation = Reservation::query()->whereKey($reservation->id)->lockForUpdate()->firstOrFail();
            $origine = $reservation->statut;

            if (! $origine->peutPasserA($cible)) {
                throw new ReservationException(__('reservation.workflow.transition', ['de' => $origine->libelle(), 'vers' => $cible->libelle()]));
            }

            $attributs = ['statut' => $cible];
            if ($cible === StatutReservation::Refusee) {
                $attributs['refus_motif'] = $motif;
            }
            if ($cible === StatutReservation::EnCours) {
                $attributs['cles_remises_le'] = now();
            }
            if ($cible === StatutReservation::Terminee) {
                $attributs['depart_effectif_le'] = now();
            }
            $reservation->update($attributs);

            if ($cible === StatutReservation::EnCours && ! $reservation->factures()->where('type', 'facture')->exists()) {
                // La facture est émise à la remise des clés : c'est là que court le délai de 48 h (RG07).
                $this->factures->emettre($reservation, $acteur);
            }

            activity('Reservation')
                ->performedOn($reservation)
                ->causedBy($acteur)
                ->event('statut')
                ->withProperties(['de' => $origine->value, 'vers' => $cible->value, 'motif' => $motif])
                ->log(__('reservation.workflow.journal', ['de' => $origine->libelle(), 'vers' => $cible->libelle()]));
        });

        if ($notifier) {
            $this->notifier($reservation, $cible, $motif);
        }

        return $reservation;
    }

    /** RG09 : une demande « En attente » non traitée depuis 24 h devient « Expirée ». */
    public function expirerEnAttente(): int
    {
        $heures = (int) Parametre::valeur('expiration_attente_heures', config('uam.pending_expiry_hours'));
        $nombre = 0;

        Reservation::query()
            ->where('statut', StatutReservation::EnAttente->value)
            ->where('created_at', '<=', now()->subHours($heures))
            ->each(function (Reservation $reservation) use (&$nombre) {
                $this->transitionner($reservation, StatutReservation::Expiree);
                $nombre++;
            });

        return $nombre;
    }

    /** RG10 : arrivée non effectuée le lendemain à l'heure limite → « Non présenté » ; la chambre est libérée. */
    public function marquerNonPresentes(): int
    {
        $tz = config('uam.timezone');
        $heure = app(AvailabilityService::class)->heureDepart();
        $nombre = 0;

        Reservation::query()
            ->where('statut', StatutReservation::Confirmee->value)
            ->where('arrivee', '<', now()->subHours(12))
            ->each(function (Reservation $reservation) use ($tz, $heure, &$nombre) {
                $limite = CarbonImmutable::instance($reservation->arrivee)->setTimezone($tz)->addDay()->setTime($heure, 0);
                if (now()->greaterThan($limite)) {
                    $this->transitionner($reservation, StatutReservation::NonPresente, notifier: false);
                    $nombre++;
                }
            });

        return $nombre;
    }

    private function notifier(Reservation $reservation, StatutReservation $cible, ?string $motif): void
    {
        $cle = match ($cible) {
            StatutReservation::Confirmee => 'confirmee',
            StatutReservation::Refusee => 'refusee',
            StatutReservation::Expiree => 'expiree',
            StatutReservation::Annulee => 'annulee',
            default => null,
        };
        if ($cle === null) {
            return;
        }

        $reservation->loadMissing('client');
        $message = __('reservation.sms.'.$cle, ['numero' => $reservation->numero, 'motif' => $motif ? ' '.$motif : '']);
        EnvoyerSms::dispatch($reservation->client->telephone, $message);
    }
}
