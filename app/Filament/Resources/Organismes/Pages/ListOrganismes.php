<?php

namespace App\Filament\Resources\Organismes\Pages;

use App\Filament\Resources\Organismes\OrganismeResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListOrganismes extends ListRecords
{
    protected static string $resource = OrganismeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
