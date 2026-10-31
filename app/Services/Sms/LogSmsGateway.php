<?php

namespace App\Services\Sms;

use Illuminate\Support\Facades\Log;

/** Développement : écrit le SMS dans le journal Laravel au lieu de l'envoyer. */
class LogSmsGateway implements SmsGateway
{
    public function envoyer(string $telephone, string $message): void
    {
        Log::info('[SMS] '.$telephone.' : '.$message);
    }
}
