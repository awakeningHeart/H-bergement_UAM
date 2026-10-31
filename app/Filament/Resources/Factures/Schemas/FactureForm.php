<?php

namespace App\Filament\Resources\Factures\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

/** Les factures sont créées par le système et immuables (RG12) : formulaire en lecture seule. */
class FactureForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->disabled()
            ->components([
                TextInput::make('numero')->label('N°'),
                TextInput::make('montant_ht')->label('HT (FCFA)'),
                TextInput::make('montant_tva')->label('TVA (FCFA)'),
                TextInput::make('montant_ttc')->label('TTC (FCFA)'),
            ]);
    }
}
