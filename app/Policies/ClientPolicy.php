<?php

namespace App\Policies;

class ClientPolicy extends PermissionPolicy
{
    protected string $voir = 'clients.voir';

    protected string $gerer = 'clients.gerer';
}
