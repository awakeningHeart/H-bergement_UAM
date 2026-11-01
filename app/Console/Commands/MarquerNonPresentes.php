<?php

namespace App\Console\Commands;

use App\Services\ReservationWorkflow;
use Illuminate\Console\Command;

class MarquerNonPresentes extends Command
{
    protected $signature = 'reservations:non-presentes';

    protected $description = 'Passe en « Non présenté » les arrivées non effectuées le lendemain à 12:00 (RG10)';

    public function handle(ReservationWorkflow $workflow): int
    {
        $this->info($workflow->marquerNonPresentes().' réservation(s) marquée(s) non présentée(s).');

        return self::SUCCESS;
    }
}
