<?php

namespace App\Filament\Widgets;

use App\Services\StatistiquesService;
use Filament\Widgets\ChartWidget;

class RevenusMensuels extends ChartWidget
{
    protected static ?int $sort = 4;

    protected ?string $heading = 'Revenus encaissés (6 derniers mois, FCFA)';

    protected ?string $maxHeight = '280px';

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        return (bool) auth()->user()?->can('reservations.voir');
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getData(): array
    {
        $donnees = (new StatistiquesService(auth()->user()))->revenusMensuels();

        return [
            'labels' => array_keys($donnees),
            'datasets' => [['label' => 'Encaissements', 'data' => array_values($donnees), 'borderColor' => '#E30613', 'backgroundColor' => 'rgba(227,6,19,.12)', 'fill' => true]],
        ];
    }
}
