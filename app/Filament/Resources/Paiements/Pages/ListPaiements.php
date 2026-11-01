<?php

namespace App\Filament\Resources\Paiements\Pages;

use App\Exports\PaiementsExport;
use App\Filament\Resources\Paiements\PaiementResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ListPaiements extends ListRecords
{
    protected static string $resource = PaiementResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('export')
                ->label('Exporter (Excel)')
                ->icon(Heroicon::OutlinedArrowDownTray)
                ->visible(fn () => auth()->user()?->can('exports.generer'))
                ->action(fn (): BinaryFileResponse => Excel::download(
                    new PaiementsExport($this->getFilteredSortedTableQuery()),
                    'paiements-'.now(config('uam.timezone'))->format('Y-m-d').'.xlsx',
                )),
        ];
    }
}
