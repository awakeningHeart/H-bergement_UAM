<?php

namespace App\Enums;

enum TypeFacture: string
{
    use Concerns\ADesOptions;

    case Facture = 'facture';
    case Avoir = 'avoir';

    public function libelle(): string
    {
        return match ($this) {
            self::Facture => 'Facture',
            self::Avoir => 'Avoir',
        };
    }
}
