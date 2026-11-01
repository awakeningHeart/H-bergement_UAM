<?php

namespace App\Filament\Resources\Reservations\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/** Historique des paiements (non modifiables) ; l'enregistrement se fait par l'action de la fiche. */
class PaiementsRelationManager extends RelationManager
{
    protected static string $relationship = 'paiements';

    protected static ?string $title = 'Paiements';

    /** @var array<string, string> */
    protected $listeners = ['reservation-mise-a-jour' => '$refresh'];

    public function isReadOnly(): bool
    {
        return true;
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                TextColumn::make('paye_le')->label('Payé le')->dateTime('d/m/Y H:i', config('uam.timezone')),
                TextColumn::make('montant')->label('Montant')->numeric(thousandsSeparator: "\u{202F}")->suffix(' FCFA'),
                TextColumn::make('mode')->label('Mode')->formatStateUsing(fn ($state) => $state->libelle()),
                TextColumn::make('reference')->label('Référence')->placeholder('—'),
                TextColumn::make('recuPar.name')->label('Reçu par')->placeholder('—'),
            ])
            ->defaultSort('paye_le', 'desc');
    }
}
