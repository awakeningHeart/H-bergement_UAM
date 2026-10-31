<?php

namespace App\Models;

use App\Models\Concerns\Audite;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

/** Personnel uniquement : les clients n'ont pas de compte. */
#[Fillable(['name', 'email', 'password', 'actif'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements FilamentUser
{
    public const ROLE_ADMIN = 'admin';

    public const ROLE_GESTIONNAIRE = 'gestionnaire';

    public const ROLE_DIRECTION = 'direction';

    /** @use HasFactory<UserFactory> */
    use Audite, HasFactory, HasRoles, Notifiable;

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'actif' => 'boolean',
        ];
    }

    public function sites(): BelongsToMany
    {
        return $this->belongsToMany(Site::class, 'site_user');
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->actif && $this->roles()->exists();
    }

    /** RG11 : l'administrateur et la direction voient tous les sites ; le gestionnaire seulement les siens. */
    public function voitTousLesSites(): bool
    {
        return $this->hasAnyRole([self::ROLE_ADMIN, self::ROLE_DIRECTION]);
    }

    public function peutAccederAuSite(?int $siteId): bool
    {
        if ($this->voitTousLesSites()) {
            return true;
        }

        return $siteId !== null && $this->sites()->where('sites.id', $siteId)->exists();
    }
}
