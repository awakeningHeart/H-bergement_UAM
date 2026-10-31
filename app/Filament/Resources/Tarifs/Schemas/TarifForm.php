<?php

namespace App\Filament\Resources\Tarifs\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class TarifForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Portée du tarif')
                    ->description('Renseigner soit un type de chambre, soit une chambre précise (le tarif de la chambre prime sur celui du type).')
                    ->schema([
                        Select::make('type_chambre_id')
                            ->label('Type de chambre')
                            ->relationship('type', 'libelle')
                            ->searchable()
                            ->preload()
                            ->requiredWithout('chambre_id')
                            ->prohibits('chambre_id'),
                        Select::make('chambre_id')
                            ->label('Chambre')
                            ->relationship('chambre', 'numero')
                            ->getOptionLabelFromRecordUsing(fn ($record) => $record->site->nom.' - n° '.$record->numero)
                            ->searchable()
                            ->preload()
                            ->requiredWithout('type_chambre_id')
                            ->prohibits('type_chambre_id'),
                    ])->columns(2),
                TextInput::make('prix_nuit')->label('Prix par nuitée (FCFA)')->required()->numeric()->integer()->minValue(0)->suffix('FCFA'),
                DatePicker::make('date_debut')->label('En vigueur à partir du')->required(),
                DatePicker::make('date_fin')->label('Jusqu\'au (vide = sans fin)')->afterOrEqual('date_debut'),
            ]);
    }
}
