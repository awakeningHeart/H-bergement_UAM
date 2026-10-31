<?php

namespace App\Filament\Resources\Clients\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class ClientForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('telephone')
                    ->label('Téléphone (format international)')
                    ->tel()
                    ->required()
                    ->rule('phone:NE,INTERNATIONAL')
                    ->unique(ignoreRecord: true)
                    ->helperText('Ex. +22790123456'),
                TextInput::make('nom')->label('Nom')->required()->maxLength(255),
                TextInput::make('prenom')->label('Prénom')->required()->maxLength(255),
                TextInput::make('titre_fonction')->label('Titre / fonction')->maxLength(255),
                TextInput::make('email')->label('E-mail')->email()->maxLength(255),
                TextInput::make('type_piece')->label('Type de pièce')->maxLength(255),
                TextInput::make('numero_piece')->label('N° de pièce')->maxLength(255),
                DatePicker::make('piece_delivree_le')->label('Pièce délivrée le'),
            ]);
    }
}
