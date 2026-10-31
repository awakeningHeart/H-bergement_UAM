<?php

namespace App\Models;

use App\Models\Concerns\Audite;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Tarif extends Model
{
    use Audite, HasFactory, SoftDeletes;

    protected $table = 'tarifs';

    protected $guarded = [];

    protected function casts(): array
    {
        return ['prix_nuit' => 'integer', 'date_debut' => 'date', 'date_fin' => 'date'];
    }

    public function type(): BelongsTo
    {
        return $this->belongsTo(TypeChambre::class, 'type_chambre_id');
    }

    public function chambre(): BelongsTo
    {
        return $this->belongsTo(Chambre::class);
    }
}
