<?php

namespace App\Models;

use App\Models\Concerns\Audite;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Client identifié par son téléphone (E.164) : pas de compte, pas de user_id. */
class Client extends Model
{
    use Audite, HasFactory;

    protected $table = 'clients';

    protected $guarded = [];

    protected function casts(): array
    {
        return ['piece_delivree_le' => 'date'];
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class);
    }

    public function nomComplet(): string
    {
        return trim($this->prenom.' '.$this->nom);
    }
}
