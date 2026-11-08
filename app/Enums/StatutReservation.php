<?php

namespace App\Enums;

enum StatutReservation: string
{
    use Concerns\ADesOptions;

    case EnAttente = 'en_attente';
    case Confirmee = 'confirmee';
    case Refusee = 'refusee';
    case Expiree = 'expiree';
    case Annulee = 'annulee';
    case EnCours = 'en_cours';
    case Terminee = 'terminee';
    case NonPresente = 'non_presente';

    /** Libellé traduit (lang/fr|en/enums.php) ; $langue force une langue, par ex. « fr » pour le journal d'audit. */
    public function libelle(?string $langue = null): string
    {
        return __('enums.statut.'.$this->value, [], $langue);
    }

    public function couleur(): string
    {
        return match ($this) {
            self::EnAttente => 'warning',
            self::Confirmee, self::Terminee => 'success',
            self::EnCours => 'info',
            self::Refusee, self::Annulee, self::NonPresente => 'danger',
            self::Expiree => 'gray',
        };
    }

    /** @return list<self> */
    public function transitionsPossibles(): array
    {
        return match ($this) {
            self::EnAttente => [self::Confirmee, self::Refusee, self::Expiree, self::Annulee],
            self::Confirmee => [self::EnCours, self::Annulee, self::NonPresente],
            self::EnCours => [self::Terminee],
            default => [],
        };
    }

    public function peutPasserA(self $cible): bool
    {
        return in_array($cible, $this->transitionsPossibles(), true);
    }

    /** Statuts qui occupent la chambre (RG02). @return list<self> */
    public static function actifs(): array
    {
        return [self::EnAttente, self::Confirmee, self::EnCours];
    }
}
