<?php

namespace App\Policies;

class TypeChambrePolicy extends PermissionPolicy
{
    protected string $voir = 'referentiels.voir';

    protected string $gerer = 'referentiels.gerer';
}
