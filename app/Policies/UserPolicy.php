<?php

namespace App\Policies;

class UserPolicy extends PermissionPolicy
{
    protected string $voir = 'utilisateurs.gerer';

    protected string $gerer = 'utilisateurs.gerer';
}
