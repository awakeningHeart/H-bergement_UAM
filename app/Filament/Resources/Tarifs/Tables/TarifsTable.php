<?php

namespace App\Filament\Resources\Tarifs\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class TarifsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('type.libelle')->label('Type de chambre')->placeholder('-'),
                TextColumn::make('chambre.numero')->label('Chambre n°')->placeholder('-'),
                TextColumn::make('prix_nuit')->label('Prix / nuitée')->numeric(thousandsSeparator: "\u{202F}")->suffix(' FCFA')->sortable(),
                TextColumn::make('date_debut')->label('Début')->date('d/m/Y')->sortable(),
                TextColumn::make('date_fin')->label('Fin')->date('d/m/Y')->placeholder('Sans fin'),
            ])
            ->defaultSort('date_debut', 'desc')
            ->filters([TrashedFilter::make()])
            ->recordActions([EditAction::make()])
            ->toolbarActions([
                BulkActionGroup::make([DeleteBulkAction::make(), RestoreBulkAction::make()]),
            ]);
    }
}
