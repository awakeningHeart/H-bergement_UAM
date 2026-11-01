<?php

namespace App\Filament\Resources\Reservations\Schemas;

use App\Enums\Motif;
use App\Enums\Payeur;
use App\Enums\StatutReservation;
use App\Models\Reservation;
use App\Support\Format;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

/**
 * Fiche de réservation du back-office. Le statut, les dates, la chambre et les montants ne se modifient pas
 * ici : le statut passe par les actions du workflow, les montants par la facturation.
 */
class ReservationForm
{
    public static function configure(Schema $schema): Schema
    {
        $fuseau = config('uam.timezone');

        return $schema
            ->components([
                Section::make('Suivi')->schema([
                    TextEntry::make('numero')->label('N° de fiche'),
                    TextEntry::make('statut')->label('Statut')->badge()
                        ->state(fn (Reservation $record) => $record->statut->libelle())
                        ->color(fn (Reservation $record) => $record->statut->couleur()),
                    TextEntry::make('solde')->label('Solde dû')
                        ->state(fn (Reservation $record) => Format::fcfa($record->soldeDu())),
                    TextEntry::make('echeance')->label('Échéance de paiement (RG07)')
                        ->state(function (Reservation $record) use ($fuseau) {
                            $echeance = $record->echeancePaiement();

                            return $echeance
                                ? $echeance->setTimezone($fuseau)->format('d/m/Y H:i').($record->soldeDu() > 0 && $echeance->isPast() ? ' — en retard' : '')
                                : ($record->payeur === Payeur::Organisme ? 'Prise en charge par un organisme' : '—');
                        }),
                    TextEntry::make('refus_motif')->label('Motif du refus')->visible(fn (Reservation $record) => filled($record->refus_motif)),
                ])->columns(4)->visibleOn('edit'),

                Section::make('Séjour')->schema([
                    Select::make('statut')->label('Statut')->options(StatutReservation::options())->disabled()->dehydrated(false)->visibleOn('create'),
                    Select::make('client_id')->label('Client')
                        ->relationship('client', 'telephone')
                        ->getOptionLabelFromRecordUsing(fn ($record) => $record->nomComplet().' ('.$record->telephone.')')
                        ->disabled()->dehydrated(false),
                    Select::make('chambre_id')->label('Chambre')
                        ->relationship('chambre', 'numero')
                        ->getOptionLabelFromRecordUsing(fn ($record) => $record->site->nom.' - n° '.$record->numero)
                        ->disabled()->dehydrated(false),
                    Select::make('motif')->label('Motif')->options(Motif::options())->required(),
                    DateTimePicker::make('arrivee')->label('Arrivée')->seconds(false)->disabled()->dehydrated(false),
                    DateTimePicker::make('depart')->label('Départ')->seconds(false)->disabled()->dehydrated(false),
                    TextInput::make('nuitees')->label('Nuitées')->disabled()->dehydrated(false),
                ])->columns(2),

                Section::make('Facturation')->schema([
                    Select::make('payeur')->label('Payeur')->options(Payeur::options())->required(),
                    Select::make('organisme_id')->label('Organisme')->relationship('organisme', 'nom')->searchable()->preload(),
                    TextInput::make('correspondant_nom')->label('Correspondant à Niamey')->maxLength(150),
                    TextInput::make('correspondant_tel')->label('Téléphone du correspondant')->tel()->maxLength(30),
                    TextInput::make('montant_ht')->label('Montant HT (FCFA)')->disabled()->dehydrated(false),
                    TextInput::make('montant_tva')->label('TVA (FCFA)')->disabled()->dehydrated(false),
                    TextInput::make('montant_ttc')->label('Montant TTC (FCFA)')->disabled()->dehydrated(false),
                ])->columns(2),
            ]);
    }
}
