<?php

namespace App\Filament\Resources\Organismes;

use App\Filament\Resources\Organismes\Pages\CreateOrganisme;
use App\Filament\Resources\Organismes\Pages\EditOrganisme;
use App\Filament\Resources\Organismes\Pages\ListOrganismes;
use App\Filament\Resources\Organismes\Schemas\OrganismeForm;
use App\Filament\Resources\Organismes\Tables\OrganismesTable;
use App\Models\Organisme;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class OrganismeResource extends Resource
{
    protected static ?string $model = Organisme::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingLibrary;

    protected static string|UnitEnum|null $navigationGroup = 'Référentiels';

    protected static ?int $navigationSort = 7;

    protected static ?string $modelLabel = 'organisme';

    protected static ?string $pluralModelLabel = 'organismes';

    protected static ?string $recordTitleAttribute = 'nom';

    public static function form(Schema $schema): Schema
    {
        return OrganismeForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return OrganismesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListOrganismes::route('/'),
            'create' => CreateOrganisme::route('/create'),
            'edit' => EditOrganisme::route('/{record}/edit'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
