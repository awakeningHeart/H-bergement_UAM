<?php

namespace App\Filament\Resources\Chambres\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class ChambresTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('site.nom')->label('Site')->sortable()->searchable(),
                TextColumn::make('numero')->label('N°')->sortable()->searchable(),
                TextColumn::make('type.libelle')->label('Type'),
                TextColumn::make('etage')->label('Étage'),
                IconColumn::make('actif')->label('Active')->boolean(),
            ])
            ->defaultSort('numero')
            ->filters([
                SelectFilter::make('site_id')->label('Site')->relationship('site', 'nom'),
                SelectFilter::make('type_chambre_id')->label('Type')->relationship('type', 'libelle'),
                TrashedFilter::make(),
            ])
            ->recordActions([EditAction::make()])
            ->toolbarActions([
                BulkActionGroup::make([DeleteBulkAction::make(), RestoreBulkAction::make()]),
            ]);
    }
}
