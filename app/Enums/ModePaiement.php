<?php

namespace App\Enums;

enum ModePaiement: string
{
    use Concerns\ADesOptions;

    case Especes = 'especes';
    case Virement = 'virement';
    case Cheque = 'cheque';
    case MobileMoney = 'mobile_money';

    public function libelle(): string
    {
        return match ($this) {
            self::Especes => 'Espèces',
            self::Virement => 'Virement',
            self::Cheque => 'Chèque',
            self::MobileMoney => 'Mobile money',
        };
    }
}
