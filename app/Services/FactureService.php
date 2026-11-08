<?php

namespace App\Services;

use App\Enums\Payeur;
use App\Enums\TypeFacture;
use App\Exceptions\ReservationException;
use App\Models\Facture;
use App\Models\Parametre;
use App\Models\Reservation;
use App\Models\Sequence;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Factures et avoirs (RG12) : numérotation séquentielle propre, document photographié à l'émission,
 * PDF figé sur disque. Une facture n'est jamais modifiée : on la corrige par un avoir.
 */
class FactureService
{
    public const DISQUE = 'local';

    /** @throws ReservationException */
    public function emettre(Reservation $reservation, ?User $acteur = null): Facture
    {
        if ($acteur !== null && ! $acteur->can('factures.gerer')) {
            throw new ReservationException(__('reservation.workflow.interdit'));
        }

        return DB::transaction(function () use ($reservation) {
            $reservation = Reservation::query()->whereKey($reservation->id)->lockForUpdate()->firstOrFail();

            // Une seule facture active par réservation : une nouvelle n'est possible qu'après un avoir.
            $active = $reservation->factures()->where('type', TypeFacture::Facture->value)
                ->whereDoesntHave('avoirs')->exists();
            if ($active) {
                throw new ReservationException(__('reservation.factures.deja_emise'));
            }

            $donnees = $this->photographier($reservation);

            return $this->creer(
                $reservation,
                TypeFacture::Facture,
                null,
                $donnees,
                $reservation->montant_ht, $reservation->montant_tva, $reservation->montant_ttc,
            );
        });
    }

    /** Avoir total d'une facture : montants négatifs, facture d'origine conservée. */
    public function emettreAvoir(Facture $facture, string $motif, ?User $acteur = null): Facture
    {
        if ($acteur !== null && ! $acteur->can('factures.gerer')) {
            throw new ReservationException(__('reservation.workflow.interdit'));
        }
        if ($facture->type !== TypeFacture::Facture) {
            throw new ReservationException(__('reservation.factures.avoir_sur_avoir'));
        }

        return DB::transaction(function () use ($facture, $motif) {
            $origine = Facture::query()->whereKey($facture->id)->lockForUpdate()->firstOrFail();
            if ($origine->avoirs()->exists()) {
                throw new ReservationException(__('reservation.factures.deja_avoir'));
            }

            $donnees = $origine->donnees ?? [];
            $donnees['motif_avoir'] = $motif;
            $donnees['facture_origine'] = $origine->numero;

            return $this->creer(
                $origine->reservation()->firstOrFail(),
                TypeFacture::Avoir,
                $origine,
                $donnees,
                -$origine->montant_ht, -$origine->montant_tva, -$origine->montant_ttc,
                $motif,
            );
        });
    }

    /** Contenu du PDF d'une facture ou d'un avoir (fichier figé à l'émission, régénéré à l'identique sinon). */
    public function contenuPdf(Facture $facture): string
    {
        if ($facture->pdf_path && Storage::disk(self::DISQUE)->exists($facture->pdf_path)) {
            return Storage::disk(self::DISQUE)->get($facture->pdf_path);
        }

        return $this->rendrePdf($facture);
    }

    private function creer(Reservation $reservation, TypeFacture $type, ?Facture $origine, array $donnees, int $ht, int $tva, int $ttc, ?string $motif = null): Facture
    {
        $annee = (int) CarbonImmutable::now(config('uam.timezone'))->year;
        $prefixe = $type === TypeFacture::Facture ? 'FAC' : 'AV';
        $numero = sprintf('%s-%d-%05d', $prefixe, $annee, Sequence::suivante($type->value, $annee));

        $facture = Facture::query()->create([
            'numero' => $numero,
            'reservation_id' => $reservation->id,
            'type' => $type,
            'facture_origine_id' => $origine?->id,
            'montant_ht' => $ht,
            'montant_tva' => $tva,
            'montant_ttc' => $ttc,
            'emise_le' => now(),
            'donnees' => $donnees,
            'motif_avoir' => $motif,
        ]);

        $chemin = 'factures/'.$numero.'.pdf';
        Storage::disk(self::DISQUE)->put($chemin, $this->rendrePdf($facture));
        $facture->update(['pdf_path' => $chemin]);   // seul champ modifiable après émission

        return $facture;
    }

    private function rendrePdf(Facture $facture): string
    {
        return Pdf::loadView('pdf.facture', [
            'facture' => $facture,
            'd' => $facture->donnees ?? [],
            'logo' => 'data:image/png;base64,'.base64_encode((string) file_get_contents(public_path('images/logo-uam-transparent.png'))),
        ])->setPaper('a4')->output();
    }

    /** @return array<string, mixed> */
    private function photographier(Reservation $reservation): array
    {
        $reservation->load(['client', 'chambre.site.ville', 'chambre.type', 'organisme', 'repas']);
        $param = fn (string $cle) => Parametre::valeur($cle);
        $tz = config('uam.timezone');

        $repas = $reservation->repas->map(fn ($l) => [
            'libelle' => $l->repas->libelle(),
            'quantite' => $l->quantite,
            'prix_unitaire' => $l->prix_unitaire,
            'montant' => $l->quantite * $l->prix_unitaire,
        ])->all();
        $montantRepas = array_sum(array_column($repas, 'montant'));
        $nuitees = $reservation->montant_ht - $montantRepas;

        $payeurOrganisme = $reservation->payeur === Payeur::Organisme && $reservation->organisme !== null;
        $client = $reservation->client;

        return [
            'etablissement' => [
                'nom' => $param('etablissement_nom'),
                'adresse' => $param('facturation_adresse'),
                'telephone' => $param('facturation_telephone'),
                'email' => $param('facturation_email'),
                'nif' => $param('facturation_nif'),
                'banque_nom' => $param('banque_nom'),
                'banque_iban' => $param('banque_iban'),
            ],
            // RG08 : facture au nom de l'organisme lorsqu'il prend en charge le séjour.
            'facture_a' => $payeurOrganisme
                ? ['nom' => $reservation->organisme->nom, 'adresse' => $reservation->organisme->adresse, 'telephone' => $reservation->organisme->telephone, 'email' => $reservation->organisme->email, 'organisme' => true]
                : ['nom' => $client->nomComplet(), 'adresse' => null, 'telephone' => $client->telephone, 'email' => $client->email, 'organisme' => false],
            'client' => ['nom' => $client->nomComplet(), 'telephone' => $client->telephone],
            'reservation' => [
                'numero' => $reservation->numero,
                'site' => $reservation->chambre->site->nom,
                'ville' => $reservation->chambre->site->ville->nom,
                'chambre' => $reservation->chambre->type->libelle.' — n° '.$reservation->chambre->numero,
                'arrivee' => $reservation->arrivee->setTimezone($tz)->format('d/m/Y H:i'),
                'depart' => $reservation->depart->setTimezone($tz)->format('d/m/Y H:i'),
                'nuitees' => $reservation->nuitees,
            ],
            'lignes' => array_merge([[
                'libelle' => 'Hébergement — '.$reservation->nuitees.' nuitée(s)',
                'quantite' => $reservation->nuitees,
                'prix_unitaire' => $reservation->nuitees > 0 ? intdiv($nuitees, $reservation->nuitees) : $nuitees,
                'montant' => $nuitees,
            ]], $repas),
            'tva_taux' => app(PricingService::class)->tauxTva(),
            'delai_paiement_heures' => $payeurOrganisme ? null : (int) ($param('delai_paiement_heures') ?? config('uam.payment_deadline_hours')),
            'conditions' => $param('conditions_generales'),
        ];
    }
}
