<?php

namespace App\Filament\Resources\Clients\Tables;

use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ClientsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('telephone')->label('Téléphone')->searchable(),
                TextColumn::make('nom')->label('Nom')->searchable()->sortable(),
                TextColumn::make('prenom')->label('Prénom')->searchable(),
                TextColumn::make('email')->label('E-mail')->toggleable(),
                TextColumn::make('reservations_count')->label('Réservations')->counts('reservations'),
            ])
            ->defaultSort('nom')
            ->recordActions([EditAction::make()]);
    }
}
