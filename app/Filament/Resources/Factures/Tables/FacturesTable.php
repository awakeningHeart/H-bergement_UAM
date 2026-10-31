<?php

namespace App\Filament\Resources\Factures\Tables;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class FacturesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('numero')->label('N°')->searchable()->sortable(),
                TextColumn::make('type')->label('Type')->badge()->formatStateUsing(fn ($state) => $state->libelle()),
                TextColumn::make('reservation.numero')->label('Réservation')->searchable(),
                TextColumn::make('montant_ht')->label('HT')->numeric(thousandsSeparator: "\u{202F}"),
                TextColumn::make('montant_tva')->label('TVA')->numeric(thousandsSeparator: "\u{202F}"),
                TextColumn::make('montant_ttc')->label('TTC')->numeric(thousandsSeparator: "\u{202F}")->suffix(' FCFA'),
                TextColumn::make('emise_le')->label('Émise le')->dateTime('d/m/Y H:i', config('uam.timezone'))->sortable(),
            ])
            ->defaultSort('emise_le', 'desc');
    }
}
