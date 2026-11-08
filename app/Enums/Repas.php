<?php

namespace App\Enums;

enum Repas: string
{
    use Concerns\ADesOptions;

    case PetitDejeuner = 'petit_dejeuner';
    case Dejeuner = 'dejeuner';
    case Diner = 'diner';

    /** Libellé traduit (lang/fr|en/enums.php) ; $langue force une langue, par ex. « fr » pour le journal d'audit. */
    public function libelle(?string $langue = null): string
    {
        return __('enums.repas.'.$this->value, [], $langue);
    }
}
