<?php

namespace App\Exceptions;

use RuntimeException;

/** Refus métier lors d'une réservation : le message est destiné au client. */
class ReservationException extends RuntimeException
{
    public const INDISPONIBLE = 'indisponible';

    public const HOLD_EXPIRE = 'hold_expire';

    public const VERIFICATION = 'verification_requise';

    public const INVALIDE = 'invalide';

    public function __construct(string $message, public readonly string $raison = self::INVALIDE)
    {
        parent::__construct($message);
    }
}
