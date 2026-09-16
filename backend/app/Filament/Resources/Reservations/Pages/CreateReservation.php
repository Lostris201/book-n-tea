<?php

namespace App\Filament\Resources\Reservations\Pages;

use App\Enums\ReservationSource;
use App\Filament\Resources\Reservations\ReservationResource;
use Filament\Resources\Pages\CreateRecord;

class CreateReservation extends CreateRecord
{
    protected static string $resource = ReservationResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['source'] = ReservationSource::Native;

        return $data;
    }
}
