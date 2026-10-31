<?php

namespace App\Filament\Resources\Parametres\Tables;

use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ParametresTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('cle')->label('Clé')->searchable()->sortable(),
                TextColumn::make('valeur')->label('Valeur')->limit(80),
            ])
            ->defaultSort('cle')
            ->recordActions([EditAction::make()]);
    }
}
