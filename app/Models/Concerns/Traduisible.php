<?php

namespace App\Models\Concerns;

/** Contenu saisi en français avec une version anglaise facultative (colonne « champ_en »). */
trait Traduisible
{
    /** Valeur du champ dans la langue courante ; à défaut de traduction, la valeur française. */
    public function traduit(string $champ): mixed
    {
        $langue = app()->getLocale();

        if ($langue !== 'fr') {
            $traduction = $this->getAttribute($champ.'_'.$langue);
            if (filled($traduction)) {
                return $traduction;
            }
        }

        return $this->getAttribute($champ);
    }
}
