<?php

namespace App\Services\Sms;

/** Passerelle SMS : le fournisseur réel est branché plus tard via .env (SMS_DRIVER). */
interface SmsGateway
{
    /** @param string $telephone numéro E.164 */
    public function envoyer(string $telephone, string $message): void;
}
