<?php

namespace App\Policies;

class AuditLogPolicy extends PermissionPolicy
{
    protected string $voir = 'audit.voir';

    protected string $gerer = 'audit.voir';
}
