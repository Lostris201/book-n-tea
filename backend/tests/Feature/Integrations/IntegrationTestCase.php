<?php

namespace Tests\Feature\Integrations;

use App\Integrations\Reservations\Providers\FakeProvider;
use App\Models\Integration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

abstract class IntegrationTestCase extends TestCase
{
    use RefreshDatabase;

    protected const WEBHOOK_SECRET = 'whsec_test_1234567890';

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        FakeProvider::reset();

        config([
            'services.reservation.provider' => 'fake',
            'services.reservation.webhook_secret' => self::WEBHOOK_SECRET,
            'services.reservation.key' => 'live_key_should_never_leak',
        ]);
    }

    protected function tearDown(): void
    {
        FakeProvider::reset();
        parent::tearDown();
    }

    protected function enableIntegration(bool $enabled = true): Integration
    {
        return tap(Integration::firstOrCreate(['provider' => 'fake']))->update(['is_enabled' => $enabled]);
    }

    protected function payload(array $overrides = []): array
    {
        return array_merge([
            'id' => 'ext-500',
            'customer_name' => 'Harici Müşteri',
            'customer_phone' => '+90 555 111 22 33',
            'party_size' => 3,
            'reserved_for' => now()->addDay()->setTime(19, 30)->toIso8601String(),
            'status' => 'confirmed',
            'note' => 'Doğum günü',
        ], $overrides);
    }

    protected function sendWebhook(array $payload, ?string $secret = self::WEBHOOK_SECRET, ?int $timestamp = null, string $provider = 'fake'): TestResponse
    {
        $body = json_encode($payload);
        $timestamp ??= now()->getTimestamp();

        $headers = [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_'.strtoupper(str_replace('-', '_', FakeProvider::TIMESTAMP_HEADER)) => (string) $timestamp,
        ];
        if ($secret !== null) {
            $headers['HTTP_'.strtoupper(str_replace('-', '_', FakeProvider::SIGNATURE_HEADER))] = FakeProvider::sign($body, $secret, $timestamp);
        }

        return $this->call('POST', "/api/webhooks/reservations/{$provider}", [], [], [], $headers, $body);
    }
}
