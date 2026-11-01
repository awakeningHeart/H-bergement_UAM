<?php

namespace App\Filament\Resources\Reservations;

use App\Filament\Concerns\RestreintAuxSites;
use App\Filament\Resources\Reservations\Pages\EditReservation;
use App\Filament\Resources\Reservations\Pages\ListReservations;
use App\Filament\Resources\Reservations\Schemas\ReservationForm;
use App\Filament\Resources\Reservations\Tables\ReservationsTable;
use App\Models\Reservation;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class ReservationResource extends Resource
{
    use RestreintAuxSites;

    protected static ?string $model = Reservation::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    protected static string|UnitEnum|null $navigationGroup = 'Réservations';

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'réservation';

    protected static ?string $pluralModelLabel = 'réservations';

    protected static ?string $recordTitleAttribute = 'numero';

    /** Les réservations naissent du parcours public (disponibilité, hold, numérotation) : pas de création manuelle. */
    public static function canCreate(): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return ReservationForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ReservationsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\FacturesRelationManager::class,
            RelationManagers\PaiementsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListReservations::route('/'),
            'view' => Pages\ViewReservation::route('/{record}'),
            'edit' => EditReservation::route('/{record}/edit'),
        ];
    }
}
