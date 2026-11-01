<?php

namespace App\Models;

use App\Enums\ModePaiement;
use App\Models\Concerns\Audite;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property ModePaiement $mode
 * @property CarbonImmutable|Carbon $paye_le
 */
class Paiement extends Model
{
    use Audite, HasFactory;

    protected $table = 'paiements';

    protected $guarded = [];

    protected function casts(): array
    {
        return ['mode' => ModePaiement::class, 'paye_le' => 'datetime', 'montant' => 'integer'];
    }

    /** @return BelongsTo<Reservation, $this> */
    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class);
    }

    public function facture(): BelongsTo
    {
        return $this->belongsTo(Facture::class);
    }

    /** @return BelongsTo<User, $this> */
    public function recuPar(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recu_par');
    }

    /** RG11 : lignes rattachées aux réservations des sites accessibles. */
    public function scopeAccessiblesPar(Builder $query, User $user): Builder
    {
        return $query->whereIn('paiements.reservation_id', Reservation::query()->accessiblesPar($user)->select('reservations.id'));
    }
}
