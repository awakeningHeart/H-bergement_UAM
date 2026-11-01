<?php

namespace App\Services;

use App\Enums\Motif;
use App\Enums\Payeur;
use App\Enums\Repas;
use App\Enums\StatutReservation;
use App\Exceptions\ReservationException;
use App\Jobs\EnvoyerSms;
use App\Models\Chambre;
use App\Models\Client;
use App\Models\Reservation;
use App\Models\Sequence;
use App\Support\Telephone;
use Carbon\CarbonImmutable;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

/**
 * Création et annulation des réservations (client sans compte).
 */
class ReservationService
{
    public function __construct(
        private readonly AvailabilityService $disponibilites,
        private readonly HoldService $holds,
        private readonly PricingService $tarification,
        private readonly ReservationWorkflow $workflow,
    ) {}

    public static function empreinteSuivi(string $jeton): string
    {
        return hash('sha256', $jeton);
    }

    /**
     * Estimation HT/TVA/TTC d'un séjour (sans rien enregistrer).
     *
     * @param  list<string>  $repas  valeurs de l'enum Repas ; quantité = nombre de nuitées
     * @return array<string, mixed>
     */
    public function estimer(Chambre $chambre, string $arrivee, string $heure, string $depart, array $repas = []): array
    {
        [$a, $d] = $this->horaires($arrivee, $heure, $depart);
        $nuitees = $this->tarification->nuitees($a, $d);

        $quantites = [];
        foreach (array_unique($repas) as $cle) {
            $quantites[Repas::from($cle)->value] = $nuitees;
        }

        try {
            return $this->tarification->devis($chambre, $a, $d, $quantites);
        } catch (InvalidArgumentException $e) {
            throw new ReservationException($e->getMessage());
        }
    }

    /**
     * Crée la réservation de façon atomique : verrou sur la chambre, revérification de la disponibilité,
     * numérotation séquentielle, consommation du hold.
     *
     * @param  array{
     *   telephone: string,
     *   identite: array<string, mixed>,
     *   sejour: array{chambre_id: int, motif: string, arrivee: string, heure: string, depart: string, repas?: list<string>},
     *   facturation: array{correspondant_nom?: ?string, correspondant_tel?: ?string, payeur: string, organisme_id?: ?int},
     *   signature: string,
     *   idempotence?: ?string
     * }  $donnees
     * @return array{reservation: Reservation, jeton: ?string, deja_creee: bool}
     *
     * @throws ReservationException
     */
    public function creer(array $donnees, ?string $jetonHold): array
    {
        $cle = isset($donnees['idempotence']) ? hash('sha256', 'idem:'.$donnees['idempotence']) : null;

        try {
            $resultat = DB::transaction(fn () => $this->creerSousVerrou($donnees, $jetonHold, $cle));
        } catch (UniqueConstraintViolationException $e) {
            // Double envoi simultané : la seconde insertion échoue sur la clé d'idempotence.
            $existante = $cle ? Reservation::query()->where('idempotence_cle', $cle)->first() : null;
            if ($existante === null) {
                throw $e;
            }

            return ['reservation' => $existante, 'jeton' => null, 'deja_creee' => true];
        }

        if (! $resultat['deja_creee']) {
            $this->notifierCreation($resultat['reservation'], (string) $resultat['jeton']);
        }

        return $resultat;
    }

    /** @return array{reservation: Reservation, jeton: ?string, deja_creee: bool} */
    private function creerSousVerrou(array $donnees, ?string $jetonHold, ?string $cle): array
    {
        if ($cle && ($existante = Reservation::query()->where('idempotence_cle', $cle)->first())) {
            return ['reservation' => $existante, 'jeton' => null, 'deja_creee' => true];
        }

        $sejour = $donnees['sejour'];
        $chambre = Chambre::query()->with('site')->whereKey($sejour['chambre_id'])->lockForUpdate()->first();
        if ($chambre === null || ! $chambre->actif || ! $chambre->site->actif) {
            throw new ReservationException(__('reservation.erreurs.indisponible'), ReservationException::INDISPONIBLE);
        }

        $hold = $this->holds->valider($jetonHold);
        if ($hold === null || $hold->chambre_id !== $chambre->id
            || $hold->arrivee->toDateString() !== $sejour['arrivee'] || $hold->depart->toDateString() !== $sejour['depart']) {
            throw new ReservationException(__('reservation.erreurs.hold_expire'), ReservationException::HOLD_EXPIRE);
        }

        // Revérification sous verrou (le hold du demandeur ne compte pas contre lui).
        if (! $this->disponibilites->estDisponible($chambre, $sejour['arrivee'], $sejour['depart'], $hold->token)) {
            throw new ReservationException(__('reservation.erreurs.indisponible'), ReservationException::INDISPONIBLE);
        }

        $client = Client::query()->where('telephone', $donnees['telephone'])->lockForUpdate()->first();
        if ($client !== null && ! Telephone::estVerifie($donnees['telephone'])) {
            throw new ReservationException(__('reservation.erreurs.verification'), ReservationException::VERIFICATION);
        }
        $identite = $donnees['identite'];
        $attributsClient = [
            'nom' => $identite['nom'],
            'prenom' => $identite['prenom'],
            'type_piece' => $identite['type_piece'] ?? null,
            'numero_piece' => $identite['numero_piece'] ?? null,
            'piece_delivree_le' => $identite['piece_delivree_le'] ?? null,
            'email' => $identite['email'] ?? null,
        ];
        $client = $client ? tap($client)->update($attributsClient) : Client::query()->create(['telephone' => $donnees['telephone']] + $attributsClient);

        [$arrivee, $depart] = $this->horaires($sejour['arrivee'], $sejour['heure'], $sejour['depart']);
        $devis = $this->estimer($chambre, $sejour['arrivee'], $sejour['heure'], $sejour['depart'], $sejour['repas'] ?? []);

        $facturation = $donnees['facturation'];
        $payeur = Payeur::from($facturation['payeur']);
        $annee = (int) CarbonImmutable::now(config('uam.timezone'))->year;
        $numero = sprintf('UAM-MH-%d-%05d', $annee, Sequence::suivante('fiche', $annee));

        $jeton = Str::random(40);
        $reservation = Reservation::query()->create([
            'numero' => $numero,
            'client_id' => $client->id,
            'chambre_id' => $chambre->id,
            'organisme_id' => $payeur === Payeur::Organisme ? ($facturation['organisme_id'] ?? null) : null,
            'motif' => Motif::from($sejour['motif']),
            'arrivee' => $arrivee->utc(),
            'depart' => $depart->utc(),
            'nuitees' => $devis['nuitees'],
            'correspondant_nom' => $facturation['correspondant_nom'] ?? null,
            'correspondant_tel' => $facturation['correspondant_tel'] ?? null,
            'payeur' => $payeur,
            'montant_ht' => $devis['montant_ht'],
            'montant_tva' => $devis['montant_tva'],
            'montant_ttc' => $devis['montant_ttc'],
            'statut' => StatutReservation::EnAttente,
            'jeton_suivi_hash' => self::empreinteSuivi($jeton),
            'idempotence_cle' => $cle,
            'signature' => $donnees['signature'],
            'conditions_acceptees_le' => now(),
        ]);

        foreach ($devis['lignes'] as $ligne) {
            if (($ligne['repas'] ?? null) !== null) {
                $reservation->repas()->create([
                    'repas' => $ligne['repas'],
                    'quantite' => $ligne['quantite'],
                    'prix_unitaire' => $ligne['prix_unitaire'],
                ]);
            }
        }

        $hold->delete();

        return ['reservation' => $reservation, 'jeton' => $jeton, 'deja_creee' => false];
    }

    private function notifierCreation(Reservation $reservation, string $jeton): void
    {
        $reservation->loadMissing('client');
        EnvoyerSms::dispatch($reservation->client->telephone, __('reservation.sms.confirmation', [
            'numero' => $reservation->numero,
            'lien' => route('suivi', $jeton),
        ]));
    }

    /** Annulation à la demande du client (En attente ou Confirmée → Annulée). */
    public function annuler(Reservation $reservation): void
    {
        if (! $reservation->statut->peutPasserA(StatutReservation::Annulee)) {
            throw new ReservationException(__('reservation.erreurs.annulation_impossible'));
        }

        // Annulation demandée par le client : journalisée, sans SMS (il vient de la demander).
        $this->workflow->transitionner($reservation, StatutReservation::Annulee, notifier: false);
    }

    /** @return array{0: CarbonImmutable, 1: CarbonImmutable} arrivée à l'heure saisie, départ à l'heure limite (heure de Niamey). */
    private function horaires(string $arrivee, string $heure, string $depart): array
    {
        $tz = config('uam.timezone');

        return [
            CarbonImmutable::parse($arrivee.' '.$heure, $tz),
            CarbonImmutable::parse($depart, $tz)->setTime($this->disponibilites->heureDepart(), 0),
        ];
    }
}
