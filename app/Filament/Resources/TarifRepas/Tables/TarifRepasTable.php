<?php

namespace App\Filament\Resources\TarifRepas\Tables;

use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class TarifRepasTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('repas')->label('Repas')->formatStateUsing(fn ($state) => $state->libelle()),
                TextColumn::make('prix')->label('Prix unitaire')->numeric(thousandsSeparator: "\u{202F}")->suffix(' FCFA'),
            ])
            ->recordActions([EditAction::make()]);
    }
}
