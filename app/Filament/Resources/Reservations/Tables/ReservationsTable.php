<?php

namespace App\Filament\Resources\Reservations\Tables;

use App\Enums\StatutReservation;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ReservationsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('numero')->label('N° de fiche')->searchable()->sortable(),
                TextColumn::make('client.telephone')->label('Client')->searchable()
                    ->description(fn ($record) => $record->client?->nomComplet()),
                TextColumn::make('chambre.site.nom')->label('Site'),
                TextColumn::make('chambre.numero')->label('Chambre'),
                TextColumn::make('arrivee')->label('Arrivée')->dateTime('d/m/Y H:i', config('uam.timezone'))->sortable(),
                TextColumn::make('depart')->label('Départ')->dateTime('d/m/Y H:i', config('uam.timezone')),
                TextColumn::make('montant_ttc')->label('Total TTC')->numeric(thousandsSeparator: "\u{202F}")->suffix(' FCFA'),
                TextColumn::make('statut')->label('Statut')->badge()
                    ->formatStateUsing(fn ($state) => $state->libelle())
                    ->color(fn ($state) => $state->couleur()),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('statut')->label('Statut')->options(StatutReservation::options()),
                SelectFilter::make('site')->label('Site')
                    ->relationship('chambre.site', 'nom'),
            ])
            ->recordActions([EditAction::make()]);
    }
}
