<?php

namespace App\Filament\Resources\Reservations\Pages;

use App\Filament\Resources\Reservations\ReservationResource;
use Filament\Resources\Pages\ViewRecord;

/** Consultation en lecture seule (Direction / Rectorat) : aucune action de workflow. */
class ViewReservation extends ViewRecord
{
    protected static string $resource = ReservationResource::class;
}
