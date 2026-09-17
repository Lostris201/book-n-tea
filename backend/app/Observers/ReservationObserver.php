<?php

namespace App\Observers;

use App\Enums\ReservationSource;
use App\Integrations\Reservations\ReservationProviderManager;
use App\Jobs\PushReservationToProvider;
use App\Models\Reservation;

class ReservationObserver
{
    /** Fields the provider cares about; other edits (e.g. table assignment, note) stay local. */
    private const PUSHED_FIELDS = ['customer_name', 'customer_phone', 'party_size', 'reserved_for', 'status'];

    public function __construct(private readonly ReservationProviderManager $manager) {}

    public function created(Reservation $reservation): void
    {
        $this->maybePush($reservation);
    }

    public function updated(Reservation $reservation): void
    {
        if ($reservation->wasChanged(self::PUSHED_FIELDS)) {
            $this->maybePush($reservation);
        }
    }

    private function maybePush(Reservation $reservation): void
    {
        if ($reservation->source !== ReservationSource::Native) {
            return;
        }

        if (! $this->manager->isEnabled() || ! $this->manager->active()->supportsPush()) {
            return;
        }

        PushReservationToProvider::dispatch($reservation->id)->afterCommit();
    }
}
