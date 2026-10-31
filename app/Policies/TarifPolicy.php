<?php

namespace App\Policies;

class TarifPolicy extends PermissionPolicy
{
    protected string $voir = 'referentiels.voir';

    protected string $gerer = 'referentiels.gerer';
}
