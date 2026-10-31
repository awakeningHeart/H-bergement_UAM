<?php

namespace App\Filament\Resources\TarifRepas\Pages;

use App\Filament\Resources\TarifRepas\TarifRepasResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListTarifRepas extends ListRecords
{
    protected static string $resource = TarifRepasResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
