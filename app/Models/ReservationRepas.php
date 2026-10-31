<?php

namespace App\Models;

use App\Enums\Repas;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReservationRepas extends Model
{
    public $timestamps = false;

    protected $table = 'reservation_repas';

    protected $guarded = [];

    protected function casts(): array
    {
        return ['repas' => Repas::class, 'quantite' => 'integer', 'prix_unitaire' => 'integer'];
    }

    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class);
    }
}
