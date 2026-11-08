<?php

namespace App\Models;

use App\Models\Concerns\Audite;
use App\Models\Concerns\Traduisible;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Site extends Model
{
    use Audite, HasFactory, SoftDeletes, Traduisible;

    protected $table = 'sites';

    protected $guarded = [];

    protected function casts(): array
    {
        return ['actif' => 'boolean'];
    }

    /** @return BelongsTo<Ville, $this> */
    public function ville(): BelongsTo
    {
        return $this->belongsTo(Ville::class);
    }

    /** @return HasMany<Chambre, $this> */
    public function chambres(): HasMany
    {
        return $this->hasMany(Chambre::class);
    }

    public function personnel(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'site_user');
    }

    public function photos(): MorphMany
    {
        return $this->morphMany(Photo::class, 'photoable')->orderBy('ordre');
    }

    /** RG11 : restreint aux sites accessibles par l'utilisateur. */
    public function scopeAccessiblesPar(Builder $query, User $user): Builder
    {
        return $user->voitTousLesSites() ? $query : $query->whereIn('sites.id', $user->sites()->select('sites.id'));
    }
}
