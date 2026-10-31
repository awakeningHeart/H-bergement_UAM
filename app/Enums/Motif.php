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

    public function libelle(): string
    {
        return match ($this) {
            self::Mission => 'Mission',
            self::Colloque => 'Colloque',
            self::Jury => 'Jury',
            self::EnseignantVisiteur => 'Enseignant visiteur',
            self::Autre => 'Autre',
        };
    }
}
