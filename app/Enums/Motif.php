<?php

namespace App\Enums;

enum Motif: string
{
    use Concerns\ADesOptions;

    case Mission = 'mission';
    case Autres = 'autres';

    public function libelle(): string
    {
        return match ($this) {
            self::Mission => 'Mission',
            self::Autres => 'Autres',
        };
    }
}
