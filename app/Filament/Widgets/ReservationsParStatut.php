<?php

namespace App\Filament\Widgets;

use App\Services\StatistiquesService;
use Filament\Widgets\ChartWidget;

class ReservationsParStatut extends ChartWidget
{
    protected static ?int $sort = 2;

    protected ?string $heading = 'Réservations par statut (arrivées du mois)';

    protected ?string $maxHeight = '280px';

    public static function canView(): bool
    {
        return (bool) auth()->user()?->can('reservations.voir');
    }

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function getData(): array
    {
        $donnees = array_filter((new StatistiquesService(auth()->user()))->parStatutDuMois());

        return [
            'labels' => array_keys($donnees),
            'datasets' => [[
                'data' => array_values($donnees),
                'backgroundColor' => ['#E30613', '#0B4EA2', '#0A1B3D', '#6B7383', '#F25A63', '#9CC3F0', '#14692F', '#B8000F'],
            ]],
        ];
    }
}
