<?php

namespace App\Integrations\Reservations;

use App\Integrations\Reservations\Providers\FakeProvider;
use App\Integrations\Reservations\Providers\NullProvider;
use App\Models\Integration;

/**
 * Resolves the reservation provider from config('services.reservation.provider').
 * The integration is active only when a known provider is configured AND an admin enabled it in the panel.
 */
class ReservationProviderManager
{
    /** Registered adapters. Add real providers here once their API docs are available. */
    public const PROVIDERS = [
        'fake' => FakeProvider::class,
    ];

    /** Configured provider name, or null when empty/unknown. */
    public function configuredName(): ?string
    {
        $name = $this->rawConfiguredName();

        return $name !== null && array_key_exists($name, self::PROVIDERS) ? $name : null;
    }

    /** Configured but not registered (typo or adapter not built yet). */
    public function misconfiguredName(): ?string
    {
        $name = $this->rawConfiguredName();

        return $name !== null && ! array_key_exists($name, self::PROVIDERS) ? $name : null;
    }

    public function isConfigured(): bool
    {
        return $this->configuredName() !== null;
    }

    public function isEnabled(): bool
    {
        return $this->isConfigured() && (bool) $this->integration()?->is_enabled;
    }

    /** The configured provider (even if disabled in the panel), or NullProvider. */
    public function configured(): ReservationProvider
    {
        return match ($this->configuredName()) {
            'fake' => new FakeProvider(config('services.reservation.webhook_secret')),
            default => new NullProvider,
        };
    }

    /** The provider to use for sync/push: NullProvider unless enabled. */
    public function active(): ReservationProvider
    {
        return $this->isEnabled() ? $this->configured() : new NullProvider;
    }

    /** Provider for an incoming webhook URL segment — only the configured, enabled one. */
    public function forWebhook(string $name): ?ReservationProvider
    {
        return $this->isEnabled() && $name === $this->configuredName() ? $this->configured() : null;
    }

    /** Settings/status row for the configured provider. */
    public function integration(): ?Integration
    {
        $name = $this->configuredName();

        return $name ? Integration::firstOrCreate(['provider' => $name], ['is_enabled' => false]) : null;
    }

    private function rawConfiguredName(): ?string
    {
        $name = strtolower(trim((string) config('services.reservation.provider')));

        return $name === '' || $name === 'null' ? null : $name;
    }
}
