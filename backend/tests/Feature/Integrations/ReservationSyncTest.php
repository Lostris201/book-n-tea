<?php

namespace Tests\Feature\Integrations;

use App\Enums\ReservationSource;
use App\Enums\ReservationStatus;
use App\Integrations\Reservations\ExternalReservationData;
use App\Integrations\Reservations\Providers\FakeProvider;
use App\Jobs\PushReservationToProvider;
use App\Models\Integration;
use App\Models\Reservation;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Queue;

class ReservationSyncTest extends IntegrationTestCase
{
    public function test_sync_command_imports_fake_reservations(): void
    {
        $this->enableIntegration();

        $this->artisan('reservations:sync')
            ->expectsOutputToContain('Synced 3 reservation(s)')
            ->assertSuccessful();

        $this->assertSame(3, Reservation::where('source', ReservationSource::External)->where('external_provider', 'fake')->count());
        $this->assertSame(ReservationStatus::Cancelled, Reservation::where('external_id', 'fake-1003')->value('status'));
        $this->assertSame(1, Reservation::where('external_id', 'fake-1002')->first()->table->number);

        $integration = Integration::where('provider', 'fake')->sole();
        $this->assertNotNull($integration->last_synced_at);
        $this->assertNull($integration->last_error);

        // Running again doesn't duplicate.
        $this->artisan('reservations:sync')->assertSuccessful();
        $this->assertSame(3, Reservation::count());
    }

    public function test_sync_respects_the_date_window(): void
    {
        $this->enableIntegration();
        FakeProvider::$remoteReservations = [
            new ExternalReservationData('near', 'Yakın', 2, CarbonImmutable::now()->addDays(2)),
            new ExternalReservationData('far', 'Uzak', 2, CarbonImmutable::now()->addDays(60)),
            new ExternalReservationData('past', 'Geçmiş', 2, CarbonImmutable::now()->subDays(10)),
        ];

        $this->artisan('reservations:sync', ['--days' => 30])->assertSuccessful();

        $this->assertSame(['near'], Reservation::pluck('external_id')->all());
    }

    public function test_sync_is_a_no_op_when_disabled_or_unconfigured(): void
    {
        $this->artisan('reservations:sync')->expectsOutputToContain('disabled')->assertSuccessful();

        config(['services.reservation.provider' => '']);
        $this->artisan('reservations:sync')->expectsOutputToContain('disabled')->assertSuccessful();

        $this->assertSame(0, Reservation::count());
    }

    public function test_unknown_provider_fails_loudly(): void
    {
        config(['services.reservation.provider' => 'opentable']);

        $this->artisan('reservations:sync')->expectsOutputToContain('Unknown reservation provider')->assertFailed();
    }

    public function test_provider_errors_are_stored_redacted(): void
    {
        $this->enableIntegration();
        FakeProvider::$remoteReservations = [];
        $this->app->bind(\App\Integrations\Reservations\ReservationProviderManager::class, fn () => new class extends \App\Integrations\Reservations\ReservationProviderManager
        {
            public function active(): \App\Integrations\Reservations\ReservationProvider
            {
                return new class(null) extends FakeProvider
                {
                    public function fetchReservations(\Carbon\CarbonInterface $from, \Carbon\CarbonInterface $to): iterable
                    {
                        throw new \RuntimeException('401 Unauthorized for https://api.example.test/bookings?api_key=live_key_should_never_leak (Bearer abc.def.ghi)');
                    }
                };
            }
        });
        $log = $this->captureLog('integrations');

        $this->artisan('reservations:sync')->assertFailed();

        $error = Integration::where('provider', 'fake')->value('last_error');
        $this->assertStringContainsString('sync: 401 Unauthorized', $error);
        $this->assertStringNotContainsString('live_key_should_never_leak', $error);
        $this->assertStringNotContainsString('abc.def.ghi', $error);
        $this->assertStringNotContainsString('live_key_should_never_leak', json_encode($log->getRecords()));
    }

    // ---- Outbound push ------------------------------------------------------------

    public function test_native_reservation_is_pushed_and_status_changes_follow(): void
    {
        $this->enableIntegration();

        $reservation = Reservation::create([
            'customer_name' => 'Panel Müşterisi',
            'party_size' => 4,
            'reserved_for' => now()->addDays(2),
            'status' => ReservationStatus::Pending,
        ]);

        $reservation->refresh();
        $this->assertSame('fake', $reservation->external_provider);
        $this->assertStringStartsWith('fake-', $reservation->external_id);
        $this->assertSame(ReservationSource::Native, $reservation->source);

        $reservation->update(['status' => ReservationStatus::Confirmed]);
        $reservation->update(['note' => 'Sadece not değişti']);

        $this->assertSame(['push', 'status'], array_column(FakeProvider::$calls, 'action'));
        $this->assertSame('confirmed', FakeProvider::$calls[1]['status']);
    }

    public function test_push_is_queued_with_retries_and_backoff(): void
    {
        Queue::fake();
        $this->enableIntegration();

        Reservation::create(['customer_name' => 'A', 'party_size' => 2, 'reserved_for' => now()->addDay()]);

        Queue::assertPushed(PushReservationToProvider::class, fn (PushReservationToProvider $job) => $job->tries === 5 && $job->backoff === [10, 60, 300, 900]);
    }

    public function test_nothing_is_pushed_when_disabled_or_for_external_reservations(): void
    {
        Queue::fake();

        Reservation::create(['customer_name' => 'Kapalıyken', 'party_size' => 2, 'reserved_for' => now()->addDay()]);
        Queue::assertNothingPushed();

        $this->enableIntegration();
        Reservation::create([
            'source' => ReservationSource::External, 'external_provider' => 'fake', 'external_id' => 'x-1',
            'customer_name' => 'Harici', 'party_size' => 2, 'reserved_for' => now()->addDay(),
        ]);
        Queue::assertNothingPushed();
    }

    public function test_push_failure_keeps_reservation_and_records_redacted_error(): void
    {
        $this->enableIntegration();
        FakeProvider::$failPushWith = 'HTTP 503 from provider; Authorization: Bearer secret.token.value';

        $reservation = Reservation::create(['customer_name' => 'Hata', 'party_size' => 2, 'reserved_for' => now()->addDay()]);

        $reservation->refresh();
        $this->assertNull($reservation->external_id);
        $error = Integration::where('provider', 'fake')->value('last_error');
        $this->assertStringContainsString('push: HTTP 503', $error);
        $this->assertStringNotContainsString('secret.token.value', $error);
    }
}
