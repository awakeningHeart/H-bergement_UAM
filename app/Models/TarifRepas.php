<?php

namespace App\Models;

use App\Enums\Repas;
use App\Models\Concerns\Audite;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TarifRepas extends Model
{
    use Audite, HasFactory;

    protected $table = 'tarifs_repas';

    protected $guarded = [];

    protected function casts(): array
    {
        return ['repas' => Repas::class, 'prix' => 'integer'];
    }
}
