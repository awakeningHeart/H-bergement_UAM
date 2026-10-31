<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Sequence extends Model
{
    protected $table = 'sequences';

    protected $guarded = [];

    /**
     * Prochaine valeur de la séquence (clé, année), atomique grâce à lockForUpdate().
     */
    public static function suivante(string $cle, ?int $annee = null): int
    {
        $annee ??= (int) now()->year;

        return DB::transaction(function () use ($cle, $annee) {
            static::query()->insertOrIgnore([
                'cle' => $cle, 'annee' => $annee, 'valeur' => 0, 'created_at' => now(), 'updated_at' => now(),
            ]);
            $sequence = static::query()->where('cle', $cle)->where('annee', $annee)->lockForUpdate()->firstOrFail();
            $sequence->valeur++;
            $sequence->save();

            return $sequence->valeur;
        });
    }
}
