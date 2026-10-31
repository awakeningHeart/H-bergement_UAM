<?php

namespace App\Filament\Resources\TypeChambres\Pages;

use App\Filament\Resources\TypeChambres\TypeChambreResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;

class EditTypeChambre extends EditRecord
{
    protected static string $resource = TypeChambreResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }
}
