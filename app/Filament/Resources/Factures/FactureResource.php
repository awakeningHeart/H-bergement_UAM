<?php

namespace App\Filament\Resources\Factures;

use App\Filament\Concerns\RestreintAuxSites;
use App\Filament\Resources\Factures\Pages\CreateFacture;
use App\Filament\Resources\Factures\Pages\EditFacture;
use App\Filament\Resources\Factures\Pages\ListFactures;
use App\Filament\Resources\Factures\Schemas\FactureForm;
use App\Filament\Resources\Factures\Tables\FacturesTable;
use App\Models\Facture;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class FactureResource extends Resource
{
    use RestreintAuxSites;

    protected static ?string $model = Facture::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static string|UnitEnum|null $navigationGroup = 'Réservations';

    protected static ?int $navigationSort = 3;

    protected static ?string $modelLabel = 'facture';

    protected static ?string $pluralModelLabel = 'factures';

    protected static ?string $recordTitleAttribute = 'numero';

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return FactureForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return FacturesTable::configure($table);
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
            'index' => ListFactures::route('/'),
            'create' => CreateFacture::route('/create'),
            'edit' => EditFacture::route('/{record}/edit'),
        ];
    }
}
