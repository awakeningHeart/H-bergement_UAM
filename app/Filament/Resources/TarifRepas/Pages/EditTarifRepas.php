<?php

namespace App\Filament\Resources\TarifRepas\Pages;

use App\Filament\Resources\TarifRepas\TarifRepasResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditTarifRepas extends EditRecord
{
    protected static string $resource = TarifRepasResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
