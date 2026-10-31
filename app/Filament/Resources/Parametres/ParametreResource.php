<?php

namespace App\Filament\Resources\Parametres;

use App\Filament\Resources\Parametres\Pages\CreateParametre;
use App\Filament\Resources\Parametres\Pages\EditParametre;
use App\Filament\Resources\Parametres\Pages\ListParametres;
use App\Filament\Resources\Parametres\Schemas\ParametreForm;
use App\Filament\Resources\Parametres\Tables\ParametresTable;
use App\Models\Parametre;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class ParametreResource extends Resource
{
    protected static ?string $model = Parametre::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static string|UnitEnum|null $navigationGroup = 'Administration';

    protected static ?int $navigationSort = 2;

    protected static ?string $modelLabel = 'paramètre';

    protected static ?string $pluralModelLabel = 'paramètres';

    protected static ?string $recordTitleAttribute = 'cle';

    public static function form(Schema $schema): Schema
    {
        return ParametreForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ParametresTable::configure($table);
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
            'index' => ListParametres::route('/'),
            'create' => CreateParametre::route('/create'),
            'edit' => EditParametre::route('/{record}/edit'),
        ];
    }
}
