<?php

namespace App\Integrations\Reservations\Providers;

use App\Integrations\Reservations\ExternalReservationData;
use App\Integrations\Reservations\IntegrationUnavailable;
use App\Integrations\Reservations\ReservationProvider;
use App\Models\Reservation;
use Carbon\CarbonInterface;
use Illuminate\Http\Request;

/**
 * Integration disabled: native reservations only. Every external operation is a no-op or refused.
 */
class NullProvider implements ReservationProvider
{
    public function name(): string
    {
        return 'null';
    }

    public function label(): string
    {
        return 'Kapalı';
    }

    public function supportsWebhooks(): bool
    {
        return false;
    }

    public function supportsPush(): bool
    {
        return false;
    }

    public function testConnection(): bool
    {
        return false;
    }

    public function fetchReservations(CarbonInterface $from, CarbonInterface $to): iterable
    {
        return [];
    }

    public function pushReservation(Reservation $reservation): string
    {
        throw new IntegrationUnavailable('Reservation integration is disabled.');
    }

    public function updateStatus(Reservation $reservation, string $status): void
    {
        throw new IntegrationUnavailable('Reservation integration is disabled.');
    }

    public function verifyWebhook(Request $request): bool
    {
        return false;
    }

    public function parseWebhook(Request $request): ExternalReservationData
    {
        throw new IntegrationUnavailable('Reservation integration is disabled.');
    }
}
