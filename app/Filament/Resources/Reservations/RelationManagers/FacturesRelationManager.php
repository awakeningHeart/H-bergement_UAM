<?php

namespace App\Filament\Resources\Reservations\RelationManagers;

use App\Enums\TypeFacture;
use App\Exceptions\ReservationException;
use App\Models\Facture;
use App\Services\FactureService;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/** Factures et avoirs (RG12) : lecture seule, correction par avoir uniquement. */
class FacturesRelationManager extends RelationManager
{
    protected static string $relationship = 'factures';

    protected static ?string $title = 'Factures et avoirs';

    /** @var array<string, string> */
    protected $listeners = ['reservation-mise-a-jour' => '$refresh'];

    public function isReadOnly(): bool
    {
        return true;
    }

    public function table(Table $table): Table
    {
        $fuseau = config('uam.timezone');

        return $table
            ->recordTitleAttribute('numero')
            ->columns([
                TextColumn::make('numero')->label('N°'),
                TextColumn::make('type')->label('Type')->badge()
                    ->formatStateUsing(fn ($state) => $state->libelle())
                    ->color(fn ($state) => $state === TypeFacture::Avoir ? 'danger' : 'success'),
                TextColumn::make('montant_ttc')->label('TTC')->numeric(thousandsSeparator: "\u{202F}")->suffix(' FCFA'),
                TextColumn::make('emise_le')->label('Émise le')->dateTime('d/m/Y H:i', $fuseau),
                TextColumn::make('motif_avoir')->label('Motif')->placeholder('—')->limit(40),
            ])
            ->defaultSort('id', 'desc')
            ->recordActions([
                Action::make('pdf')->label('PDF')->icon(Heroicon::OutlinedArrowDownTray)
                    ->url(fn (Facture $record) => route('admin.facture.pdf', $record))->openUrlInNewTab(),
                Action::make('avoir')->label('Émettre un avoir')->icon(Heroicon::OutlinedReceiptRefund)->color('danger')
                    ->visible(fn (Facture $record) => $record->type === TypeFacture::Facture
                        && ! $record->avoirs()->exists() && auth()->user()?->can('factures.gerer'))
                    ->modalDescription('L\'avoir annule intégralement la facture. La facture d\'origine reste conservée.')
                    ->schema([Textarea::make('motif')->label('Motif de l\'avoir')->required()->maxLength(300)])
                    ->action(function (Facture $record, array $data) {
                        try {
                            $avoir = app(FactureService::class)->emettreAvoir($record, $data['motif'], auth()->user());
                        } catch (ReservationException $e) {
                            Notification::make()->title($e->getMessage())->danger()->send();

                            return;
                        }
                        Notification::make()->title('Avoir '.$avoir->numero.' émis.')->success()->send();
                        $this->dispatch('reservation-mise-a-jour');
                    }),
            ]);
    }
}
