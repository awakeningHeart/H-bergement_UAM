<?php

namespace App\Policies;

use App\Models\Chambre;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class ReservationPolicy extends PermissionPolicy
{
    protected string $voir = 'reservations.voir';

    protected string $gerer = 'reservations.gerer';

    protected function parSite(): bool
    {
        return true;
    }

    /** @param Reservation $model */
    protected function siteId(Model $model): ?int
    {
        return Chambre::withTrashed()->whereKey($model->chambre_id)->value('site_id');
    }

    /** Une réservation n'est jamais supprimée : elle change de statut (workflow). */
    public function delete(User $user, Model $model): bool
    {
        return false;
    }

    public function restore(User $user, Model $model): bool
    {
        return false;
    }
}
