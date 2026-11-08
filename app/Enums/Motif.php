<?php

namespace App\Enums;

enum Motif: string
{
    use Concerns\ADesOptions;

    case Mission = 'mission';
    case Colloque = 'colloque';
    case Jury = 'jury';
    case EnseignantVisiteur = 'enseignant_visiteur';
    case Autre = 'autre';

    /** Libellé traduit (lang/fr|en/enums.php) ; $langue force une langue, par ex. « fr » pour le journal d'audit. */
    public function libelle(?string $langue = null): string
    {
        return __('enums.motif.'.$this->value, [], $langue);
    }
}
