<?php

namespace App\Enums;

enum Repas: string
{
    use Concerns\ADesOptions;

    case PetitDejeuner = 'petit_dejeuner';
    case Dejeuner = 'dejeuner';
    case Diner = 'diner';

    public function libelle(): string
    {
        return match ($this) {
            self::PetitDejeuner => 'Petit-déjeuner',
            self::Dejeuner => 'Déjeuner',
            self::Diner => 'Dîner',
        };
    }
}
