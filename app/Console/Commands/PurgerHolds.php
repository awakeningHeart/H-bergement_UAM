<?php

namespace App\Console\Commands;

use App\Services\HoldService;
use Illuminate\Console\Command;

class PurgerHolds extends Command
{
    protected $signature = 'holds:purger';

    protected $description = 'Libère les holds de chambre expirés (RG13)';

    public function handle(HoldService $holds): int
    {
        $this->info($holds->purger().' hold(s) libéré(s).');

        return self::SUCCESS;
    }
}
