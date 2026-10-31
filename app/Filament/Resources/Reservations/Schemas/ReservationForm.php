<?php

namespace App\Filament\Resources\Reservations\Schemas;

use App\Enums\Motif;
use App\Enums\Payeur;
use App\Enums\StatutReservation;
use App\Models\Chambre;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ReservationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Séjour')->schema([
                    TextInput::make('numero')->label('N° de fiche')->disabled()->dehydrated(false)->placeholder('Attribué à la création'),
                    Select::make('statut')->label('Statut')->options(StatutReservation::options())->default(StatutReservation::EnAttente->value)->required(),
                    Select::make('client_id')->label('Client')
                        ->relationship('client', 'telephone')
                        ->getOptionLabelFromRecordUsing(fn ($record) => $record->nomComplet().' ('.$record->telephone.')')
                        ->searchable(['telephone', 'nom', 'prenom'])
                        ->required(),
                    Select::make('chambre_id')->label('Chambre')
                        ->options(fn () => Chambre::query()->accessiblesPar(auth()->user())->with('site')->get()
                            ->mapWithKeys(fn (Chambre $c) => [$c->id => $c->site->nom.' - n° '.$c->numero]))
                        ->searchable()
                        ->required(),
                    Select::make('motif')->label('Motif')->options(Motif::options())->required(),
                    DateTimePicker::make('arrivee')->label('Arrivée')->required()->seconds(false),
                    DateTimePicker::make('depart')->label('Départ')->required()->seconds(false)->after('arrivee'),
                    TextInput::make('nuitees')->label('Nuitées')->numeric()->integer()->minValue(1)->required(),
                ])->columns(2),
                Section::make('Facturation')->schema([
                    Select::make('payeur')->label('Payeur')->options(Payeur::options())->default(Payeur::Client->value)->required(),
                    Select::make('organisme_id')->label('Organisme')->relationship('organisme', 'nom')->searchable()->preload(),
                    TextInput::make('correspondant_nom')->label('Correspondant à Niamey')->maxLength(255),
                    TextInput::make('correspondant_tel')->label('Téléphone du correspondant')->tel()->maxLength(20),
                    TextInput::make('montant_ht')->label('Montant HT (FCFA)')->numeric()->integer()->minValue(0)->default(0)->required(),
                    TextInput::make('montant_tva')->label('TVA (FCFA)')->numeric()->integer()->minValue(0)->default(0)->required(),
                    TextInput::make('montant_ttc')->label('Montant TTC (FCFA)')->numeric()->integer()->minValue(0)->default(0)->required(),
                ])->columns(2),
            ]);
    }
}
