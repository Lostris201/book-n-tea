<?php

namespace Tests\Feature\Integrations;

use App\Enums\ReservationSource;
use App\Enums\ReservationStatus;
use App\Models\CafeTable;
use App\Models\Reservation;

class ReservationWebhookTest extends IntegrationTestCase
{
    public function test_valid_webhook_creates_external_reservation(): void
    {
        $this->enableIntegration();

        $this->sendWebhook($this->payload(['table_number' => 5]))
            ->assertStatus(202)
            ->assertExactJson(['received' => true]);

        $reservation = Reservation::sole();
        $this->assertSame(ReservationSource::External, $reservation->source);
        $this->assertSame('fake', $reservation->external_provider);
        $this->assertSame('ext-500', $reservation->external_id);
        $this->assertSame('Harici Müşteri', $reservation->customer_name);
        $this->assertSame(3, $reservation->party_size);
        $this->assertSame(ReservationStatus::Confirmed, $reservation->status);
        $this->assertTrue($reservation->table->is(CafeTable::where('number', 5)->first()));
    }

    public function test_same_webhook_twice_is_idempotent_and_updates_apply(): void
    {
        $this->enableIntegration();

        $this->sendWebhook($this->payload())->assertStatus(202);
        $this->sendWebhook($this->payload())->assertStatus(202);
        $this->assertSame(1, Reservation::count());

        // Provider is the source of truth for external reservations.
        $this->sendWebhook($this->payload(['status' => 'cancelled', 'party_size' => 5]))->assertStatus(202);
        $reservation = Reservation::sole();
        $this->assertSame(ReservationStatus::Cancelled, $reservation->status);
        $this->assertSame(5, $reservation->party_size);
    }

    public function test_bad_signature_is_rejected_and_security_logged(): void
    {
        $this->enableIntegration();
        $security = $this->captureLog('security');

        $this->sendWebhook($this->payload(), secret: 'wrong-secret')
            ->assertStatus(401)
            ->assertExactJson(['error' => 'Geçersiz imza.']);

        $this->assertSame(0, Reservation::count());
        $this->assertTrue($security->hasWarningThatContains('Webhook signature verification failed'));
        $this->assertStringNotContainsString(self::WEBHOOK_SECRET, json_encode($security->getRecords()));
    }

    public function test_missing_signature_or_tampered_body_is_rejected(): void
    {
        $this->enableIntegration();

        $this->sendWebhook($this->payload(), secret: null)->assertStatus(401);

        // Signature for one body, different body sent.
        $timestamp = now()->getTimestamp();
        $signature = \App\Integrations\Reservations\Providers\FakeProvider::sign(json_encode($this->payload()), self::WEBHOOK_SECRET, $timestamp);
        $this->call('POST', '/api/webhooks/reservations/fake', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_FAKE_TIMESTAMP' => (string) $timestamp,
            'HTTP_X_FAKE_SIGNATURE' => $signature,
        ], json_encode($this->payload(['party_size' => 99])))->assertStatus(401);

        $this->assertSame(0, Reservation::count());
    }

    public function test_stale_timestamp_is_rejected(): void
    {
        $this->enableIntegration();

        $this->sendWebhook($this->payload(), timestamp: now()->subMinutes(10)->getTimestamp())->assertStatus(401);
        $this->assertSame(0, Reservation::count());
    }

    public function test_webhooks_are_refused_when_no_secret_is_configured(): void
    {
        $this->enableIntegration();
        config(['services.reservation.webhook_secret' => '']);

        // Even a signature made with the empty secret must not pass.
        $this->sendWebhook($this->payload(), secret: '')->assertStatus(401);
        $this->assertSame(0, Reservation::count());
    }

    public function test_disabled_unconfigured_or_unknown_provider_returns_404(): void
    {
        $this->enableIntegration(false);
        $this->sendWebhook($this->payload())->assertStatus(404);

        $this->enableIntegration();
        $this->sendWebhook($this->payload(), provider: 'other')->assertStatus(404);

        config(['services.reservation.provider' => '']);
        $this->sendWebhook($this->payload())->assertStatus(404);

        $this->assertSame(0, Reservation::count());
    }

    public function test_invalid_payload_returns_422(): void
    {
        $this->enableIntegration();

        $this->sendWebhook($this->payload(['party_size' => 'many']))->assertStatus(422)->assertExactJson(['error' => 'Geçersiz veri.']);
        $this->sendWebhook($this->payload(['status' => 'weird']))->assertStatus(422);
        $this->sendWebhook(['id' => 'x'])->assertStatus(422);
        $this->assertSame(0, Reservation::count());
    }

    public function test_provider_echo_does_not_overwrite_native_reservation(): void
    {
        $this->enableIntegration();

        $native = Reservation::create([
            'customer_name' => 'Panel Rezervasyonu',
            'party_size' => 2,
            'reserved_for' => now()->addDay(),
            'status' => ReservationStatus::Confirmed,
        ]);
        $native->refresh();
        $this->assertNotNull($native->external_id, 'Native reservation should have been pushed to the provider.');

        $this->sendWebhook($this->payload(['id' => $native->external_id, 'customer_name' => 'Sağlayıcıdaki İsim', 'status' => 'cancelled']))
            ->assertStatus(202);

        $native->refresh();
        $this->assertSame(ReservationSource::Native, $native->source);
        $this->assertSame('Panel Rezervasyonu', $native->customer_name);
        $this->assertSame(ReservationStatus::Confirmed, $native->status);
        $this->assertSame(1, Reservation::count());
    }
}
