<?php

namespace App\Console\Commands;

use App\Models\OtpCode;
use Illuminate\Console\Command;

class PurgerOtp extends Command
{
    protected $signature = 'otp:purger';

    protected $description = 'Supprime les codes OTP de plus de 24 heures';

    public function handle(): int
    {
        $this->info(OtpCode::query()->where('envoye_le', '<', now()->subDay())->delete().' code(s) supprimé(s).');

        return self::SUCCESS;
    }
}
