<?php

namespace App\Filament\Resources\Villes;

use App\Filament\Resources\Villes\Pages\CreateVille;
use App\Filament\Resources\Villes\Pages\EditVille;
use App\Filament\Resources\Villes\Pages\ListVilles;
use App\Filament\Resources\Villes\Schemas\VilleForm;
use App\Filament\Resources\Villes\Tables\VillesTable;
use App\Models\Ville;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class VilleResource extends Resource
{
    protected static ?string $model = Ville::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMapPin;

    protected static string|UnitEnum|null $navigationGroup = 'Référentiels';

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'ville';

    protected static ?string $pluralModelLabel = 'villes';

    protected static ?string $recordTitleAttribute = 'nom';

    public static function form(Schema $schema): Schema
    {
        return VilleForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return VillesTable::configure($table);
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
            'index' => ListVilles::route('/'),
            'create' => CreateVille::route('/create'),
            'edit' => EditVille::route('/{record}/edit'),
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
