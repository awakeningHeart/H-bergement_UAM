<?php

namespace App\Filament\Resources\TypeChambres;

use App\Filament\Resources\TypeChambres\Pages\CreateTypeChambre;
use App\Filament\Resources\TypeChambres\Pages\EditTypeChambre;
use App\Filament\Resources\TypeChambres\Pages\ListTypeChambres;
use App\Filament\Resources\TypeChambres\Schemas\TypeChambreForm;
use App\Filament\Resources\TypeChambres\Tables\TypeChambresTable;
use App\Models\TypeChambre;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class TypeChambreResource extends Resource
{
    protected static ?string $model = TypeChambre::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSquares2x2;

    protected static string|UnitEnum|null $navigationGroup = 'Référentiels';

    protected static ?int $navigationSort = 3;

    protected static ?string $modelLabel = 'type de chambre';

    protected static ?string $pluralModelLabel = 'types de chambre';

    protected static ?string $recordTitleAttribute = 'libelle';

    public static function form(Schema $schema): Schema
    {
        return TypeChambreForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TypeChambresTable::configure($table);
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
            'index' => ListTypeChambres::route('/'),
            'create' => CreateTypeChambre::route('/create'),
            'edit' => EditTypeChambre::route('/{record}/edit'),
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
