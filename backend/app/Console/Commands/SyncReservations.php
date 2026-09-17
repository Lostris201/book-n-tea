<?php

namespace App\Console\Commands;

use App\Integrations\Reservations\ReservationProviderManager;
use App\Integrations\Reservations\ReservationSync;
use Illuminate\Console\Command;
use Throwable;

/**
 * Polling fallback for providers without webhooks. Scheduled every 5 minutes; no-op when disabled.
 */
class SyncReservations extends Command
{
    protected $signature = 'reservations:sync {--days= : How many days ahead to fetch (default: RESERVATION_SYNC_DAYS)}';

    protected $description = 'Import reservations from the external reservation system';

    public function handle(ReservationProviderManager $manager, ReservationSync $sync): int
    {
        if ($name = $manager->misconfiguredName()) {
            $this->error("Unknown reservation provider \"{$name}\". Check RESERVATION_PROVIDER.");

            return self::FAILURE;
        }

        if (! $manager->isEnabled()) {
            $this->info('Reservation integration is disabled; nothing to sync.');

            return self::SUCCESS;
        }

        $days = (int) ($this->option('days') ?: config('services.reservation.sync_days', 30));

        try {
            $count = $sync->syncWindow(now()->startOfDay()->subDay(), now()->addDays(max(1, $days))->endOfDay());
        } catch (Throwable $e) {
            $this->error('Sync failed; see the Integrations page for details.');

            return self::FAILURE;
        }

        $this->info("Synced {$count} reservation(s) from {$manager->configured()->label()}.");

        return self::SUCCESS;
    }
}
