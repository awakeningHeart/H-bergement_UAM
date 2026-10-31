<?php

namespace App\Policies;

class ParametrePolicy extends PermissionPolicy
{
    protected string $voir = 'parametres.gerer';

    protected string $gerer = 'parametres.gerer';
}
