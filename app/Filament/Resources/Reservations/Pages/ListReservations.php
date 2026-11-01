<?php

namespace App\Filament\Resources\Reservations\Pages;

use App\Exports\ReservationsExport;
use App\Filament\Resources\Reservations\ReservationResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ListReservations extends ListRecords
{
    protected static string $resource = ReservationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('export')
                ->label('Exporter (Excel)')
                ->icon(Heroicon::OutlinedArrowDownTray)
                ->visible(fn () => auth()->user()?->can('exports.generer'))
                ->action(fn (): BinaryFileResponse => Excel::download(
                    new ReservationsExport($this->getFilteredSortedTableQuery()),
                    'reservations-'.now(config('uam.timezone'))->format('Y-m-d').'.xlsx',
                )),
        ];
    }
}
