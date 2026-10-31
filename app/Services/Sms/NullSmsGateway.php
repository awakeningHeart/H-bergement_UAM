<?php

namespace App\Services\Sms;

/** N'envoie rien (tests, environnements sans passerelle). */
class NullSmsGateway implements SmsGateway
{
    public function envoyer(string $telephone, string $message): void {}
}
