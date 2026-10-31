<?php

namespace App\Models;

use App\Models\Concerns\Audite;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Organisme extends Model
{
    use Audite, HasFactory, SoftDeletes;

    protected $table = 'organismes';

    protected $guarded = [];

    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class);
    }
}
