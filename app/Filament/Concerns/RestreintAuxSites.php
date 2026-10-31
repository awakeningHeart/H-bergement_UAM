<?php

namespace App\Filament\Concerns;

use Illuminate\Database\Eloquent\Builder;

/** RG11 : le gestionnaire ne voit que les enregistrements de ses sites (le modèle expose scopeAccessiblesPar). */
trait RestreintAuxSites
{
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = auth()->user();

        return $user ? $query->scopes(['accessiblesPar' => [$user]]) : $query->whereRaw('1 = 0');
    }
}
