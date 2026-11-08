<?php

namespace App\Support;

class Format
{
    /** « 25 000 » : séparateur de milliers en espace insécable fine (docs/05-ui-guidelines.md). */
    public static function nombre(int $valeur): string
    {
        return number_format($valeur, 0, ',', "\u{202F}");
    }

    /** « 25 000 FCFA » avec espace insécable avant la devise. */
    public static function fcfa(int $valeur): string
    {
        return self::nombre($valeur)."\u{00A0}".config('uam.currency', 'FCFA');
    }

    /** Heure pleine dans la langue courante : « 12h00 » en français, « 12:00 » en anglais. */
    public static function heure(int $heure): string
    {
        return __('site.format_heure', ['h' => sprintf('%02d', $heure)]);
    }
}
