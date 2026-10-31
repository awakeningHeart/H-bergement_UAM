<?php

namespace App\Policies;

class OrganismePolicy extends PermissionPolicy
{
    protected string $voir = 'referentiels.voir';

    protected string $gerer = 'referentiels.gerer';
}
