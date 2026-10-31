<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * Base des Policies : droits par permission, restreints aux sites de l'utilisateur (RG11)
 * lorsque le modèle est rattaché à un site.
 */
abstract class PermissionPolicy
{
    protected string $voir = 'referentiels.voir';

    protected string $gerer = 'referentiels.gerer';

    /** Identifiant du site auquel appartient le modèle, ou null s'il est global. */
    protected function siteId(Model $model): ?int
    {
        return null;
    }

    /** Le modèle est-il rattaché à un site (donc soumis à RG11) ? */
    protected function parSite(): bool
    {
        return false;
    }

    private function dansPerimetre(User $user, Model $model): bool
    {
        return ! $this->parSite() || $user->peutAccederAuSite($this->siteId($model));
    }

    public function viewAny(User $user): bool
    {
        return $user->can($this->voir) || $user->can($this->gerer);
    }

    public function view(User $user, Model $model): bool
    {
        return $this->viewAny($user) && $this->dansPerimetre($user, $model);
    }

    public function create(User $user): bool
    {
        return $user->can($this->gerer);
    }

    public function update(User $user, Model $model): bool
    {
        return $user->can($this->gerer) && $this->dansPerimetre($user, $model);
    }

    public function delete(User $user, Model $model): bool
    {
        return $this->update($user, $model);
    }

    public function restore(User $user, Model $model): bool
    {
        return $this->update($user, $model);
    }

    public function forceDelete(User $user, Model $model): bool
    {
        return false;
    }
}
