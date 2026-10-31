<?php

namespace App\Models;

use App\Enums\TypeFacture;
use App\Models\Concerns\Audite;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

class Facture extends Model
{
    use Audite, HasFactory;

    protected $table = 'factures';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'type' => TypeFacture::class,
            'emise_le' => 'datetime',
            'montant_ht' => 'integer',
            'montant_tva' => 'integer',
            'montant_ttc' => 'integer',
        ];
    }

    /** RG12 : une facture émise est immuable (seul le chemin du PDF peut être renseigné). */
    protected static function booted(): void
    {
        static::updating(function (self $facture) {
            $modifies = array_diff(array_keys($facture->getDirty()), ['pdf_path', 'updated_at']);
            if ($modifies !== []) {
                throw new LogicException('Une facture émise est immuable : émettre un avoir pour la corriger (RG12).');
            }
        });
        static::deleting(fn () => throw new LogicException('Une facture émise ne peut pas être supprimée (RG12).'));
    }

    /** @return BelongsTo<Reservation, $this> */
    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class);
    }

    public function origine(): BelongsTo
    {
        return $this->belongsTo(self::class, 'facture_origine_id');
    }

    /** RG11 : lignes rattachées aux réservations des sites accessibles. */
    public function scopeAccessiblesPar(Builder $query, User $user): Builder
    {
        return $query->whereIn('factures.reservation_id', Reservation::query()->accessiblesPar($user)->select('reservations.id'));
    }
}
