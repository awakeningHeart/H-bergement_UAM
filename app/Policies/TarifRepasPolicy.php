<?php

namespace App\Policies;

class TarifRepasPolicy extends PermissionPolicy
{
    protected string $voir = 'referentiels.voir';

    protected string $gerer = 'referentiels.gerer';
}
