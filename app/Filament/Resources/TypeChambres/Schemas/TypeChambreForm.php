<?php

namespace App\Filament\Resources\TypeChambres\Schemas;

use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class TypeChambreForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('libelle')->label('Libellé')->required()->maxLength(255),
                TextInput::make('libelle_en')->label('Libellé en anglais')->helperText('Facultatif : à défaut, le libellé français est affiché.')->maxLength(255),
                TextInput::make('capacite')->label('Capacité (personnes)')->required()->numeric()->minValue(1)->default(1),
                TagsInput::make('equipements')->label('Équipements')->columnSpanFull(),
                TagsInput::make('equipements_en')->label('Équipements en anglais')->helperText('Facultatif : à défaut, la liste française est affichée.')->columnSpanFull(),
            ]);
    }
}
