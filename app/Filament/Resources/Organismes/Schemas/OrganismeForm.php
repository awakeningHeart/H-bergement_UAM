<?php

namespace App\Filament\Resources\Organismes\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class OrganismeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('nom')->label('Nom')->required()->maxLength(255),
                TextInput::make('contact')->label('Personne à contacter')->maxLength(255),
                TextInput::make('telephone')->label('Téléphone')->tel()->maxLength(20),
                TextInput::make('email')->label('E-mail')->email()->maxLength(255),
                TextInput::make('adresse')->label('Adresse')->maxLength(255)->columnSpanFull(),
            ]);
    }
}
