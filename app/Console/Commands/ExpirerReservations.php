<?php

namespace App\Console\Commands;

use App\Services\ReservationWorkflow;
use Illuminate\Console\Command;

class ExpirerReservations extends Command
{
    protected $signature = 'reservations:expirer';

    protected $description = 'Passe en « Expirée » les demandes en attente depuis plus de 24 h (RG09)';

    public function handle(ReservationWorkflow $workflow): int
    {
        $this->info($workflow->expirerEnAttente().' demande(s) expirée(s).');

        return self::SUCCESS;
    }
}
