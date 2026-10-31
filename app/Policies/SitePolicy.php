<?php

namespace App\Policies;

use App\Models\Site;
use Illuminate\Database\Eloquent\Model;

class SitePolicy extends PermissionPolicy
{
    protected function parSite(): bool
    {
        return true;
    }

    /** @param Site $model */
    protected function siteId(Model $model): ?int
    {
        return $model->id;
    }
}
