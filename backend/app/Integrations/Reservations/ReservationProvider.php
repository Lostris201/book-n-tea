<?php

namespace App\Integrations\Reservations;

use App\Models\Reservation;
use Carbon\CarbonInterface;
use Illuminate\Http\Request;

/**
 * Adapter for a café's external reservation system. A real integration is one class implementing this.
 * Don't guess a provider's endpoints or payloads — implement only from its real API docs.
 */
interface ReservationProvider
{
    /** Short machine name, stored as reservations.external_provider and used in the webhook URL. */
    public function name(): string;

    /** Human-readable name for the admin panel. */
    public function label(): string;

    public function supportsWebhooks(): bool;

    /** Whether reservations created in our panel can be sent to the provider. */
    public function supportsPush(): bool;

    public function testConnection(): bool;

    /** @return iterable<ExternalReservationData> */
    public function fetchReservations(CarbonInterface $from, CarbonInterface $to): iterable;

    /** @return string external_id assigned by the provider */
    public function pushReservation(Reservation $reservation): string;

    public function updateStatus(Reservation $reservation, string $status): void;

    public function verifyWebhook(Request $request): bool;

    /** @throws InvalidProviderPayload */
    public function parseWebhook(Request $request): ExternalReservationData;
}
