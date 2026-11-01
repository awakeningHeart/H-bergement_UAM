<?php

namespace App\Filament\Resources\Reservations\Pages;

use App\Enums\ModePaiement;
use App\Enums\StatutReservation;
use App\Exceptions\ReservationException;
use App\Filament\Resources\Reservations\ReservationResource;
use App\Models\Reservation;
use App\Services\FactureService;
use App\Services\PaiementService;
use App\Services\ReservationWorkflow;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;

/** @property Reservation $record */
class EditReservation extends EditRecord
{
    protected static string $resource = ReservationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            $this->actionStatut('confirmer', 'Confirmer', StatutReservation::Confirmee, 'success', Heroicon::OutlinedCheckCircle),
            $this->actionStatut('refuser', 'Refuser', StatutReservation::Refusee, 'danger', Heroicon::OutlinedXCircle, avecMotif: true),
            $this->actionStatut('arrivee', 'Arrivée / remise des clés', StatutReservation::EnCours, 'info', Heroicon::OutlinedKey),
            $this->actionStatut('depart', 'Départ', StatutReservation::Terminee, 'success', Heroicon::OutlinedArrowRightOnRectangle),
            $this->actionStatut('non_presente', 'Non présenté', StatutReservation::NonPresente, 'warning', Heroicon::OutlinedUserMinus),
            $this->actionStatut('annuler', 'Annuler', StatutReservation::Annulee, 'gray', Heroicon::OutlinedNoSymbol),
            $this->actionFacture(),
            $this->actionPaiement(),
        ];
    }

    private function actionStatut(string $nom, string $libelle, StatutReservation $cible, string $couleur, Heroicon $icone, bool $avecMotif = false): Action
    {
        $action = Action::make($nom)
            ->label($libelle)
            ->icon($icone)
            ->color($couleur)
            ->requiresConfirmation()
            ->visible(fn () => $this->record->statut->peutPasserA($cible) && auth()->user()?->can('update', $this->record))
            ->action(function (array $data) use ($cible) {
                try {
                    app(ReservationWorkflow::class)->transitionner($this->record, $cible, auth()->user(), $data['motif'] ?? null);
                } catch (ReservationException $e) {
                    Notification::make()->title($e->getMessage())->danger()->send();

                    return;
                }
                $this->rafraichir();
                Notification::make()->title(__('reservation.workflow.'.$cible->value))->success()->send();
            });

        if ($avecMotif) {
            $action->schema([Textarea::make('motif')->label('Motif du refus (communiqué au client)')->maxLength(200)]);
        }

        return $action;
    }

    private function actionFacture(): Action
    {
        return Action::make('facturer')
            ->label('Émettre la facture')
            ->icon(Heroicon::OutlinedDocumentText)
            ->color('primary')
            ->requiresConfirmation()
            ->modalDescription('Une facture émise ne peut plus être modifiée : on la corrige par un avoir.')
            ->visible(fn () => in_array($this->record->statut, [StatutReservation::Confirmee, StatutReservation::EnCours, StatutReservation::Terminee], true)
                && auth()->user()?->can('factures.gerer')
                && ! $this->record->factures()->where('type', 'facture')->whereDoesntHave('avoirs')->exists())
            ->action(function () {
                try {
                    $facture = app(FactureService::class)->emettre($this->record, auth()->user());
                } catch (ReservationException $e) {
                    Notification::make()->title($e->getMessage())->danger()->send();

                    return;
                }
                $this->rafraichir();
                Notification::make()->title('Facture '.$facture->numero.' émise.')->success()->send();
            });
    }

    private function actionPaiement(): Action
    {
        return Action::make('paiement')
            ->label('Enregistrer un paiement')
            ->icon(Heroicon::OutlinedBanknotes)
            ->color('success')
            ->visible(fn () => in_array($this->record->statut, [StatutReservation::Confirmee, StatutReservation::EnCours, StatutReservation::Terminee], true)
                && auth()->user()?->can('paiements.gerer') && auth()->user()->can('update', $this->record)
                && $this->record->soldeDu() > 0)
            ->fillForm(fn () => ['montant' => $this->record->soldeDu(), 'mode' => ModePaiement::Especes->value])
            ->schema([
                TextInput::make('montant')->label('Montant (FCFA)')->numeric()->integer()->minValue(1)->required()->suffix('FCFA'),
                Select::make('mode')->label('Mode de paiement')->options(ModePaiement::options())->required(),
                TextInput::make('reference')->label('Référence (n° de chèque, de virement…)')->maxLength(255),
            ])
            ->action(function (array $data) {
                try {
                    app(PaiementService::class)->enregistrer(
                        $this->record, (int) $data['montant'], ModePaiement::from($data['mode']), $data['reference'] ?? null, auth()->user(),
                    );
                } catch (ReservationException $e) {
                    Notification::make()->title($e->getMessage())->danger()->send();

                    return;
                }
                $this->rafraichir();
                Notification::make()->title('Paiement enregistré.')->success()->send();
            });
    }

    private function rafraichir(): void
    {
        $this->record->refresh();
        $this->refreshFormData(['statut', 'refus_motif', 'cles_remises_le']);
        $this->dispatch('reservation-mise-a-jour');
    }
}
