<?php

namespace App\Filament\Resources\Sites\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class SitesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nom')->label('Site')->searchable()->sortable(),
                TextColumn::make('ville.nom')->label('Ville')->sortable(),
                TextColumn::make('chambres_count')->label('Chambres')->counts('chambres'),
                TextColumn::make('telephone')->label('Téléphone'),
                IconColumn::make('actif')->label('Actif')->boolean(),
            ])
            ->defaultSort('nom')
            ->filters([
                SelectFilter::make('ville_id')->label('Ville')->relationship('ville', 'nom'),
                TrashedFilter::make(),
            ])
            ->recordActions([EditAction::make()])
            ->toolbarActions([
                BulkActionGroup::make([DeleteBulkAction::make(), RestoreBulkAction::make()]),
            ]);
    }
}
