<?php

namespace App\Models;

use App\Models\Concerns\Audite;
use App\Models\Concerns\Traduisible;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Chambre extends Model
{
    use Audite, HasFactory, SoftDeletes, Traduisible;

    protected $table = 'chambres';

    protected $guarded = [];

    protected function casts(): array
    {
        return ['actif' => 'boolean'];
    }

    /** @return BelongsTo<Site, $this> */
    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    /** @return BelongsTo<TypeChambre, $this> */
    public function type(): BelongsTo
    {
        return $this->belongsTo(TypeChambre::class, 'type_chambre_id');
    }

    public function tarifs(): HasMany
    {
        return $this->hasMany(Tarif::class);
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class);
    }

    public function holds(): HasMany
    {
        return $this->hasMany(ChambreHold::class);
    }

    public function photos(): MorphMany
    {
        return $this->morphMany(Photo::class, 'photoable')->orderBy('ordre');
    }

    public function scopeAccessiblesPar(Builder $query, User $user): Builder
    {
        return $user->voitTousLesSites() ? $query : $query->whereIn('chambres.site_id', $user->sites()->select('sites.id'));
    }

    /**
     * Tarif en vigueur à une date : tarif propre à la chambre, sinon tarif du type (RG06).
     */
    public function tarifA(CarbonInterface $date): ?Tarif
    {
        $jour = $date->toDateString();
        $enVigueur = fn (Builder $q) => $q->where('date_debut', '<=', $jour)
            ->where(fn (Builder $q) => $q->whereNull('date_fin')->orWhere('date_fin', '>=', $jour))
            ->orderByDesc('date_debut');

        $tarif = $enVigueur(Tarif::query()->where('chambre_id', $this->id))->first()
            ?? $enVigueur(Tarif::query()->whereNull('chambre_id')->where('type_chambre_id', $this->type_chambre_id))->first();

        return $tarif instanceof Tarif ? $tarif : null;
    }
}
