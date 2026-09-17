<?php

namespace App\Jobs;

use App\Enums\ReservationSource;
use App\Integrations\Reservations\ReservationProviderManager;
use App\Integrations\Reservations\ReservationSync;
use App\Models\Reservation;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Jobs\SyncJob;
use Throwable;

/**
 * Sends a native reservation (or its status change) to the external provider, with retries and backoff.
 */
class PushReservationToProvider implements ShouldQueue
{
    use Queueable;

    public int $tries = 5;

    /** @var list<int> */
    public array $backoff = [10, 60, 300, 900];

    public function __construct(public readonly int $reservationId) {}

    public function handle(ReservationProviderManager $manager, ReservationSync $sync): void
    {
        $reservation = Reservation::find($this->reservationId);
        $provider = $manager->active();

        if (! $reservation || $reservation->source !== ReservationSource::Native || ! $provider->supportsPush()) {
            return;
        }

        try {
            if ($reservation->external_id === null) {
                $externalId = $provider->pushReservation($reservation);

                // Quiet save: don't trigger another push for our own bookkeeping.
                $reservation->forceFill([
                    'external_provider' => $provider->name(),
                    'external_id' => $externalId,
                ])->saveQuietly();
            } else {
                $provider->updateStatus($reservation, $reservation->status->value);
            }

            $manager->integration()?->update(['last_error' => null]);
        } catch (Throwable $e) {
            $sync->recordError($e, 'push');

            // Real queues retry with backoff; the sync driver (local/tests) must not break the panel request.
            if (! $this->job instanceof SyncJob) {
                throw $e;
            }
        }
    }
}
