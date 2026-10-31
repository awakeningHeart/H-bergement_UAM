<?php

namespace App\Filament\Resources\TypeChambres\Pages;

use App\Filament\Resources\TypeChambres\TypeChambreResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListTypeChambres extends ListRecords
{
    protected static string $resource = TypeChambreResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
