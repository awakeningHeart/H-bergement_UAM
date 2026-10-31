<?php

namespace App\Filament\Resources\Chambres;

use App\Filament\Concerns\RestreintAuxSites;
use App\Filament\Resources\Chambres\Pages\CreateChambre;
use App\Filament\Resources\Chambres\Pages\EditChambre;
use App\Filament\Resources\Chambres\Pages\ListChambres;
use App\Filament\Resources\Chambres\Schemas\ChambreForm;
use App\Filament\Resources\Chambres\Tables\ChambresTable;
use App\Models\Chambre;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class ChambreResource extends Resource
{
    use RestreintAuxSites;

    protected static ?string $model = Chambre::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedHome;

    protected static string|UnitEnum|null $navigationGroup = 'Référentiels';

    protected static ?int $navigationSort = 4;

    protected static ?string $modelLabel = 'chambre';

    protected static ?string $pluralModelLabel = 'chambres';

    protected static ?string $recordTitleAttribute = 'numero';

    public static function form(Schema $schema): Schema
    {
        return ChambreForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ChambresTable::configure($table);
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
            'index' => ListChambres::route('/'),
            'create' => CreateChambre::route('/create'),
            'edit' => EditChambre::route('/{record}/edit'),
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
