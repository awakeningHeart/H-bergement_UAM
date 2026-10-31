<?php

namespace App\Filament\Resources\Paiements\Tables;

use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PaiementsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('reservation.numero')->label('Réservation')->searchable(),
                TextColumn::make('montant')->label('Montant')->numeric(thousandsSeparator: "\u{202F}")->suffix(' FCFA')->sortable(),
                TextColumn::make('mode')->label('Mode')->formatStateUsing(fn ($state) => $state->libelle()),
                TextColumn::make('reference')->label('Référence'),
                TextColumn::make('paye_le')->label('Payé le')->dateTime('d/m/Y H:i', config('uam.timezone'))->sortable(),
                TextColumn::make('recuPar.name')->label('Reçu par'),
            ])
            ->defaultSort('paye_le', 'desc')
            ->recordActions([EditAction::make()]);
    }
}
