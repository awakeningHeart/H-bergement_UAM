<?php

namespace App\Support;

use libphonenumber\NumberParseException;
use Propaganistas\LaravelPhone\PhoneNumber;

/** Normalisation des numéros en E.164 (RG04) : région par défaut NE, numéros internationaux acceptés. */
class Telephone
{
    public const REGION = 'NE';

    /** Numéro E.164 valide, ou null. */
    public static function normaliser(?string $saisie): ?string
    {
        $saisie = trim((string) $saisie);
        if ($saisie === '' || strlen($saisie) > 30) {
            return null;
        }

        try {
            $numero = new PhoneNumber($saisie, self::REGION);

            return $numero->isValid() ? $numero->formatE164() : null;
        } catch (NumberParseException) {
            return null;
        }
    }

    /** Preuve (en session) qu'un téléphone a validé son code OTP : valable 30 minutes. */
    public static function marquerVerifie(string $telephone): void
    {
        session()->put('otp.verifies.'.sha1($telephone), now()->addMinutes(30)->getTimestamp());
    }

    public static function estVerifie(string $telephone): bool
    {
        $jusqua = session()->get('otp.verifies.'.sha1($telephone));

        return is_int($jusqua) && $jusqua > now()->getTimestamp();
    }

    public static function oublier(string $telephone): void
    {
        session()->forget('otp.verifies.'.sha1($telephone));
    }

    /** Masque un numéro pour l'affichage : +227 ••• ••• 00. */
    public static function masquer(string $telephone): string
    {
        return substr($telephone, 0, 4).str_repeat('•', max(0, strlen($telephone) - 6)).substr($telephone, -2);
    }
}
