<?php

namespace App\Policies;

use App\Models\Chambre;
use App\Models\Paiement;
use Illuminate\Database\Eloquent\Model;

class PaiementPolicy extends PermissionPolicy
{
    protected string $voir = 'reservations.voir';

    protected string $gerer = 'paiements.gerer';

    protected function parSite(): bool
    {
        return true;
    }

    /** @param Paiement $model */
    protected function siteId(Model $model): ?int
    {
        $chambreId = $model->reservation?->chambre_id;

        return $chambreId === null ? null : Chambre::withTrashed()->whereKey($chambreId)->value('site_id');
    }
}
