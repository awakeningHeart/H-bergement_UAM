<?php

namespace App\Filament\Resources\Users\Tables;

use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('Nom')->searchable()->sortable(),
                TextColumn::make('email')->label('E-mail')->searchable(),
                TextColumn::make('roles.name')->label('Rôle')->badge(),
                TextColumn::make('sites.nom')->label('Sites')->badge()->limitList(3),
                IconColumn::make('actif')->label('Actif')->boolean(),
            ])
            ->recordActions([EditAction::make()]);
    }
}
