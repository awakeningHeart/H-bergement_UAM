<?php

namespace App\Enums;

enum Payeur: string
{
    use Concerns\ADesOptions;

    case Client = 'client';
    case Organisme = 'organisme';

    public function libelle(): string
    {
        return match ($this) {
            self::Client => 'Client',
            self::Organisme => 'Organisme',
        };
    }
}
