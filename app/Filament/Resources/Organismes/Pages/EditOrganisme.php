<?php

namespace App\Filament\Resources\Organismes\Pages;

use App\Filament\Resources\Organismes\OrganismeResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;

class EditOrganisme extends EditRecord
{
    protected static string $resource = OrganismeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }
}
