<?php

namespace App\Models;

use App\Enums\Motif;
use App\Enums\Payeur;
use App\Enums\StatutReservation;
use App\Models\Concerns\Audite;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
            'nuitees' => 'integer',
            'montant_ht' => 'integer',
            'montant_tva' => 'integer',
            'montant_ttc' => 'integer',
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function chambre(): BelongsTo
    {
        return $this->belongsTo(Chambre::class);
    }

    public function organisme(): BelongsTo
    {
        return $this->belongsTo(Organisme::class);
    }

    public function repas(): HasMany
    {
        return $this->hasMany(ReservationRepas::class);
    }

    public function factures(): HasMany
    {
        return $this->hasMany(Facture::class);
    }

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
}
