<?php

namespace App\Enums\Concerns;

trait ADesOptions
{
    /** @return array<string, string> valeur => libellé, pour les listes déroulantes. */
    public static function options(): array
    {
        $options = [];
        foreach (self::cases() as $cas) {
            $options[$cas->value] = $cas->libelle();
        }

        return $options;
    }
}
