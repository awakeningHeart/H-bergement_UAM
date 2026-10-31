<?php

namespace App\Filament\Resources\TarifRepas\Schemas;

use App\Enums\Repas;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class TarifRepasForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('repas')->label('Repas')->options(Repas::options())->required()->unique(ignoreRecord: true),
                TextInput::make('prix')->label('Prix unitaire (FCFA)')->required()->numeric()->integer()->minValue(0)->suffix('FCFA'),
            ]);
    }
}
