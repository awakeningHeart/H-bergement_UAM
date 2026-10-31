<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChambreHold extends Model
{
    use HasFactory;

    protected $table = 'chambre_holds';

    protected $guarded = [];

    protected function casts(): array
    {
        return ['arrivee' => 'date', 'depart' => 'date', 'expire_le' => 'datetime'];
    }

    public function chambre(): BelongsTo
    {
        return $this->belongsTo(Chambre::class);
    }

    public function scopeActifs(Builder $query): Builder
    {
        return $query->where('expire_le', '>', now());
    }
}
