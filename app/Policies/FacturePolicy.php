<?php

namespace App\Policies;

use App\Models\Chambre;
use App\Models\Facture;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class FacturePolicy extends PermissionPolicy
{
    protected string $voir = 'reservations.voir';

    protected string $gerer = 'factures.gerer';

    protected function parSite(): bool
    {
        return true;
    }

    /** @param Facture $model */
    protected function siteId(Model $model): ?int
    {
        $chambreId = $model->reservation?->chambre_id;

        return $chambreId === null ? null : Chambre::withTrashed()->whereKey($chambreId)->value('site_id');
    }

    /** RG12 : une facture émise ne se modifie ni ne se supprime ; correction par avoir. */
    public function update(User $user, Model $model): bool
    {
        return false;
    }

    public function delete(User $user, Model $model): bool
    {
        return false;
    }

    public function restore(User $user, Model $model): bool
    {
        return false;
    }
}
