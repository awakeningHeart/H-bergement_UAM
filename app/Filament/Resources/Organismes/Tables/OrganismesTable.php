<?php

namespace App\Filament\Resources\Organismes\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class OrganismesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nom')->label('Organisme')->searchable()->sortable(),
                TextColumn::make('contact')->label('Contact'),
                TextColumn::make('telephone')->label('Téléphone'),
                TextColumn::make('email')->label('E-mail'),
            ])
            ->defaultSort('nom')
            ->filters([TrashedFilter::make()])
            ->recordActions([EditAction::make()])
            ->toolbarActions([
                BulkActionGroup::make([DeleteBulkAction::make(), RestoreBulkAction::make()]),
            ]);
    }
}
