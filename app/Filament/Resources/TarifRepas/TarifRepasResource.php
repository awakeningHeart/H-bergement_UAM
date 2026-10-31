<?php

namespace App\Filament\Resources\TarifRepas;

use App\Filament\Resources\TarifRepas\Pages\CreateTarifRepas;
use App\Filament\Resources\TarifRepas\Pages\EditTarifRepas;
use App\Filament\Resources\TarifRepas\Pages\ListTarifRepas;
use App\Filament\Resources\TarifRepas\Schemas\TarifRepasForm;
use App\Filament\Resources\TarifRepas\Tables\TarifRepasTable;
use App\Models\TarifRepas;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class TarifRepasResource extends Resource
{
    protected static ?string $model = TarifRepas::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCake;

    protected static string|UnitEnum|null $navigationGroup = 'Référentiels';

    protected static ?int $navigationSort = 6;

    protected static ?string $modelLabel = 'tarif repas';

    protected static ?string $pluralModelLabel = 'tarifs repas';

    public static function form(Schema $schema): Schema
    {
        return TarifRepasForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TarifRepasTable::configure($table);
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
            'index' => ListTarifRepas::route('/'),
            'create' => CreateTarifRepas::route('/create'),
            'edit' => EditTarifRepas::route('/{record}/edit'),
        ];
    }
}
