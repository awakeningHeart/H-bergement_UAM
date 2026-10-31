<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')->label('Nom')->required()->maxLength(255),
                TextInput::make('email')->label('E-mail')->email()->required()->unique(ignoreRecord: true)->maxLength(255),
                TextInput::make('password')
                    ->label('Mot de passe')
                    ->password()
                    ->revealable()
                    ->minLength(10)
                    ->required(fn (string $operation) => $operation === 'create')
                    ->dehydrated(fn (?string $state) => filled($state))
                    ->helperText('Laisser vide pour conserver le mot de passe actuel.'),
                Select::make('roles')->label('Rôle')->relationship('roles', 'name')->preload()->required(),
                Select::make('sites')->label('Sites affectés (gestionnaire)')->relationship('sites', 'nom')->multiple()->preload(),
                Toggle::make('actif')->label('Compte actif')->default(true),
            ]);
    }
}
