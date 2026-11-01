<?php

namespace App\Models;

use App\Enums\Motif;
use App\Enums\Payeur;
use App\Enums\StatutReservation;
use App\Models\Concerns\Audite;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property StatutReservation $statut
 * @property Motif $motif
 * @property Payeur $payeur
 * @property CarbonImmutable|Carbon $arrivee
 * @property CarbonImmutable|Carbon $depart
 * @property CarbonImmutable|Carbon|null $conditions_acceptees_le
 * @property CarbonImmutable|Carbon|null $cles_remises_le
 * @property CarbonImmutable|Carbon|null $depart_effectif_le
 */
class Reservation extends Model
{
    use Audite, HasFactory;

    protected $table = 'reservations';

    protected $guarded = [];

    protected $hidden = ['jeton_suivi_hash', 'signature'];

    protected function casts(): array
    {
        return [
            'motif' => Motif::class,
            'payeur' => Payeur::class,
            'statut' => StatutReservation::class,
            'arrivee' => 'datetime',
            'depart' => 'datetime',
            'conditions_acceptees_le' => 'datetime',
            'cles_remises_le' => 'datetime',
            'depart_effectif_le' => 'datetime',
            'nuitees' => 'integer',
            'montant_ht' => 'integer',
            'montant_tva' => 'integer',
            'montant_ttc' => 'integer',
        ];
    }

    /** @return BelongsTo<Client, $this> */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /** @return BelongsTo<Chambre, $this> */
    public function chambre(): BelongsTo
    {
        return $this->belongsTo(Chambre::class);
    }

    /** @return BelongsTo<Organisme, $this> */
    public function organisme(): BelongsTo
    {
        return $this->belongsTo(Organisme::class);
    }

    /** @return HasMany<ReservationRepas, $this> */
    public function repas(): HasMany
    {
        return $this->hasMany(ReservationRepas::class);
    }

    /** @return HasMany<Facture, $this> */
    public function factures(): HasMany
    {
        return $this->hasMany(Facture::class);
    }

    /** @return HasMany<Paiement, $this> */
    public function paiements(): HasMany
    {
        return $this->hasMany(Paiement::class);
    }

    /** RG11 : réservations des sites accessibles par l'utilisateur. */
    public function scopeAccessiblesPar(Builder $query, User $user): Builder
    {
        return $user->voitTousLesSites()
            ? $query
            : $query->whereIn('reservations.chambre_id', Chambre::query()->accessiblesPar($user)->select('chambres.id'));
    }

    /** RG02 : réservations actives qui chevauchent [arrivée, départ[. */
    public function scopeChevauchant(Builder $query, int $chambreId, mixed $arrivee, mixed $depart): Builder
    {
        return $query->where('chambre_id', $chambreId)
            ->whereIn('statut', array_map(fn (StatutReservation $s) => $s->value, StatutReservation::actifs()))
            ->where('arrivee', '<', $depart)
            ->where('depart', '>', $arrivee);
    }

    /**
     * Solde dû en FCFA : total des factures (avoirs déduits) ou, à défaut, le TTC de la réservation
     * tant qu'elle est active, moins les paiements reçus.
     */
    public function soldeDu(): int
    {
        if ($this->factures()->exists()) {
            $du = (int) $this->factures()->sum('montant_ttc');
        } elseif (in_array($this->statut, [StatutReservation::EnAttente, StatutReservation::Refusee, StatutReservation::Expiree, StatutReservation::Annulee, StatutReservation::NonPresente], true)) {
            $du = 0;
        } else {
            $du = $this->montant_ttc;
        }

        return $du - (int) $this->paiements()->sum('montant');
    }

    /** RG07 : paiement dû 48 h après la remise des clés, sauf prise en charge par un organisme. */
    public function echeancePaiement(): ?CarbonImmutable
    {
        if ($this->payeur === Payeur::Organisme || $this->cles_remises_le === null) {
            return null;
        }
        $heures = (int) Parametre::valeur('delai_paiement_heures', config('uam.payment_deadline_hours'));

        return CarbonImmutable::instance($this->cles_remises_le)->addHours($heures);
    }

    /** Jeton du QR code de la fiche : numéro + signature, vérifié par la route /admin/scan/{token}. */
    public function tokenScan(): string
    {
        return $this->numero.'.'.self::signatureScan($this->numero);
    }

    public static function numeroDepuisScan(string $token): ?string
    {
        $position = strrpos($token, '.');
        if ($position === false) {
            return null;
        }
        $numero = substr($token, 0, $position);

        return hash_equals(self::signatureScan($numero), substr($token, $position + 1)) ? $numero : null;
    }

    private static function signatureScan(string $numero): string
    {
        return substr(hash_hmac('sha256', 'scan:'.$numero, (string) config('app.key')), 0, 20);
    }
}
