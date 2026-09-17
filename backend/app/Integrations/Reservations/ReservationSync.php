<?php

namespace App\Integrations\Reservations;

use App\Enums\ReservationSource;
use App\Models\CafeTable;
use App\Models\Reservation;
use App\Support\LogRedactor;
use Carbon\CarbonInterface;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

/**
 * Conflict rule: the provider is the source of truth for external reservations; we are the source of
 * truth for native ones (a provider echo of a reservation we pushed never overwrites it).
 */
class ReservationSync
{
    public function __construct(private readonly ReservationProviderManager $manager) {}

    /** Idempotent: keyed by (external_provider, external_id). */
    public function upsert(ReservationProvider $provider, ExternalReservationData $data): Reservation
    {
        $key = ['external_provider' => $provider->name(), 'external_id' => $data->externalId];

        $existing = Reservation::where($key)->first();

        if ($existing && $existing->source === ReservationSource::Native) {
            return $existing;
        }

        $attributes = [
            'source' => ReservationSource::External,
            'customer_name' => $data->customerName,
            'customer_phone' => $data->customerPhone,
            'party_size' => $data->partySize,
            'reserved_for' => $data->reservedFor,
            'status' => $data->status,
            'note' => $data->note,
        ];

        if ($data->tableNumber !== null) {
            $tableId = CafeTable::where('number', $data->tableNumber)->value('id');
            if ($tableId) {
                $attributes['cafe_table_id'] = $tableId;
            }
        }

        try {
            return Reservation::updateOrCreate($key, $attributes);
        } catch (UniqueConstraintViolationException) {
            // A concurrent webhook created it between our read and insert.
            return tap(Reservation::where($key)->firstOrFail())->update($attributes);
        }
    }

    /**
     * Pulls reservations in the window and upserts them. Records last_synced_at / last_error.
     *
     * @return int number of reservations processed
     *
     * @throws IntegrationUnavailable when the integration isn't enabled
     */
    public function syncWindow(CarbonInterface $from, CarbonInterface $to): int
    {
        if (! $this->manager->isEnabled()) {
            throw new IntegrationUnavailable('Reservation integration is not enabled.');
        }

        $provider = $this->manager->active();
        $integration = $this->manager->integration();

        try {
            $count = 0;
            foreach ($provider->fetchReservations($from, $to) as $data) {
                $this->upsert($provider, $data);
                $count++;
            }

            $integration->update(['last_synced_at' => now(), 'last_error' => null]);

            return $count;
        } catch (Throwable $e) {
            $this->recordError($e, 'sync');

            throw $e;
        }
    }

    /** Stores a redacted, truncated error on the integration row and logs it. */
    public function recordError(Throwable $e, string $operation): void
    {
        $message = Str::limit(LogRedactor::redactString($e->getMessage()), 1000);

        $this->manager->integration()?->update(['last_error' => '['.now()->format('d.m.Y H:i').'] '.$operation.': '.$message]);

        Log::channel('integrations')->error('Reservation integration error', [
            'provider' => $this->manager->configuredName(),
            'operation' => $operation,
            'exception' => $e::class,
            'message' => $message,
        ]);
    }
}
