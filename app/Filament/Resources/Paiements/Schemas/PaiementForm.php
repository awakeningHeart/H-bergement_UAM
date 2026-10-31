<?php

namespace App\Filament\Resources\Paiements\Schemas;

use App\Enums\ModePaiement;
use App\Models\Reservation;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class PaiementForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('reservation_id')->label('Réservation')
                    ->options(fn () => Reservation::query()->accessiblesPar(auth()->user())->latest()->limit(200)->pluck('numero', 'id'))
                    ->searchable()
                    ->required(),
                TextInput::make('montant')->label('Montant (FCFA)')->required()->numeric()->integer()->minValue(1)->suffix('FCFA'),
                Select::make('mode')->label('Mode de paiement')->options(ModePaiement::options())->required(),
                TextInput::make('reference')->label('Référence')->maxLength(255),
                DateTimePicker::make('paye_le')->label('Payé le')->default(now())->required()->seconds(false),
                Select::make('recu_par')->label('Reçu par')->relationship('recuPar', 'name')->default(auth()->id()),
            ]);
    }
}
