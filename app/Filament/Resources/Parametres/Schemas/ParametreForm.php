<?php

namespace App\Filament\Resources\Parametres\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class ParametreForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('cle')->label('Clé')->required()->disabled(fn (string $operation) => $operation === 'edit')->unique(ignoreRecord: true),
                Textarea::make('valeur')->label('Valeur')->rows(4)->columnSpanFull(),
            ]);
    }
}
