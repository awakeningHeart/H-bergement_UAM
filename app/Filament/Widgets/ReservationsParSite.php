<?php

namespace App\Filament\Widgets;

use App\Services\StatistiquesService;
use Filament\Widgets\ChartWidget;

class ReservationsParSite extends ChartWidget
{
    protected static ?int $sort = 3;

    protected ?string $heading = 'Réservations par site (arrivées du mois)';

    protected ?string $maxHeight = '280px';

    public static function canView(): bool
    {
        return (bool) auth()->user()?->can('reservations.voir');
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getData(): array
    {
        $donnees = (new StatistiquesService(auth()->user()))->parSiteDuMois();

        return [
            'labels' => array_keys($donnees),
            'datasets' => [['label' => 'Réservations', 'data' => array_values($donnees), 'backgroundColor' => '#0B4EA2']],
        ];
    }
}
