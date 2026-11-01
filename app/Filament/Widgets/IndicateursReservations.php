<?php

namespace App\Filament\Widgets;

use App\Services\StatistiquesService;
use App\Support\Format;
use Carbon\CarbonImmutable;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class IndicateursReservations extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected ?string $heading = 'Aujourd\'hui et ce mois-ci';

    public static function canView(): bool
    {
        return (bool) auth()->user()?->can('reservations.voir');
    }

    protected function getStats(): array
    {
        $stats = new StatistiquesService(auth()->user());
        $maintenant = CarbonImmutable::now(config('uam.timezone'));

        return [
            Stat::make('Demandes en attente', $stats->enAttente())->description('À traiter')->color('warning'),
            Stat::make('Arrivées du jour', $stats->arriveesDuJour())->description('Réservations confirmées')->color('info'),
            Stat::make('Départs du jour', $stats->departsDuJour())->description('Séjours en cours')->color('gray'),
            Stat::make('Taux d\'occupation du mois', number_format($stats->tauxOccupation($maintenant->startOfMonth(), $maintenant->startOfMonth()->addMonth()), 1, ',', "\u{202F}").' %')
                ->description('Nuitées occupées / nuitées disponibles')->color('success'),
            Stat::make('Revenus encaissés (mois)', Format::fcfa($stats->revenusDuMois()))->description('Paiements reçus')->color('success'),
            Stat::make('Impayés', Format::fcfa($stats->impayes()))->description('Séjours en cours ou terminés')->color('danger'),
        ];
    }
}
