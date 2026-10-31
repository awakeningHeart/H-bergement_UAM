<?php

namespace Tests\Support;

use App\Services\Sms\SmsGateway;

/** Passerelle SMS de test : conserve les messages au lieu de les envoyer. */
class SmsCapture implements SmsGateway
{
    /** @var list<array{telephone: string, message: string}> */
    public array $envoyes = [];

    public function envoyer(string $telephone, string $message): void
    {
        $this->envoyes[] = ['telephone' => $telephone, 'message' => $message];
    }

    public function dernier(): ?array
    {
        return $this->envoyes[array_key_last($this->envoyes)] ?? null;
    }

    /** Code OTP à 6 chiffres du dernier SMS. */
    public function dernierCode(): ?string
    {
        preg_match('/\b(\d{6})\b/', $this->dernier()['message'] ?? '', $m);

        return $m[1] ?? null;
    }
}
