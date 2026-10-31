<?php

namespace App\Models;

use App\Models\Concerns\Audite;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class TypeChambre extends Model
{
    use Audite, HasFactory, SoftDeletes;

    protected $table = 'types_chambre';

    protected $guarded = [];

    protected function casts(): array
    {
        return ['equipements' => 'array', 'capacite' => 'integer'];
    }

    public function chambres(): HasMany
    {
        return $this->hasMany(Chambre::class);
    }

    public function tarifs(): HasMany
    {
        return $this->hasMany(Tarif::class);
    }
}
