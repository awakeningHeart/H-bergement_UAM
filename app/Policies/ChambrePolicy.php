<?php

namespace App\Policies;

use App\Models\Chambre;
use Illuminate\Database\Eloquent\Model;

class ChambrePolicy extends PermissionPolicy
{
    protected function parSite(): bool
    {
        return true;
    }

    /** @param Chambre $model */
    protected function siteId(Model $model): ?int
    {
        return $model->site_id;
    }
}
