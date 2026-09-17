<?php

namespace App\Jobs;

use App\Integrations\Reservations\ExternalReservationData;
use App\Integrations\Reservations\ReservationProviderManager;
use App\Integrations\Reservations\ReservationSync;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

/** Webhook payload is verified and parsed in the request; the database work happens here. */
class ProcessReservationWebhook implements ShouldQueue
{
    use Queueable;

    public int $tries = 5;

    /** @var list<int> */
    public array $backoff = [10, 60, 300];

    public function __construct(public readonly string $provider, public readonly array $data) {}

    public function handle(ReservationProviderManager $manager, ReservationSync $sync): void
    {
        $provider = $manager->forWebhook($this->provider);

        // Integration disabled since the webhook arrived: drop it.
        if (! $provider) {
            return;
        }

        $sync->upsert($provider, ExternalReservationData::fromArray($this->data));
    }

    public function failed(Throwable $e): void
    {
        app(ReservationSync::class)->recordError($e, 'webhook');
    }
}
