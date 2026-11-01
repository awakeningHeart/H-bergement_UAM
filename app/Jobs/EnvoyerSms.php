<?php

namespace App\Jobs;

use App\Services\Sms\SmsGateway;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/** Envoi d'un SMS en file d'attente (driver database) : un échec du fournisseur ne bloque jamais une action métier. */
class EnvoyerSms implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /** @var list<int> */
    public array $backoff = [30, 120];

    public function __construct(public string $telephone, public string $message)
    {
        $this->afterCommit = true;
    }

    public function handle(SmsGateway $sms): void
    {
        $sms->envoyer($this->telephone, $this->message);
    }
}
