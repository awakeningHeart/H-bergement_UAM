<?php

namespace App\Models;

use App\Models\Concerns\Audite;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Parametre extends Model
{
    use Audite;

    protected $table = 'parametres';

    protected $primaryKey = 'cle';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = [];

    protected function casts(): array
    {
        return ['valeur' => 'json'];
    }

    protected static function booted(): void
    {
        $oublier = fn () => Cache::forget('parametres');
        static::saved($oublier);
        static::deleted($oublier);
    }

    /** Valeur d'un paramètre (cache 1 h). */
    public static function valeur(string $cle, mixed $defaut = null): mixed
    {
        $tous = Cache::remember('parametres', 3600, fn () => static::query()->pluck('valeur', 'cle')->all());

        return $tous[$cle] ?? $defaut;
    }
}
