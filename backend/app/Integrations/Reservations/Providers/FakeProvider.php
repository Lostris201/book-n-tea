<?php

namespace App\Integrations\Reservations\Providers;

use App\Enums\ReservationStatus;
use App\Integrations\Reservations\ExternalReservationData;
use App\Integrations\Reservations\InvalidProviderPayload;
use App\Integrations\Reservations\ReservationProvider;
use App\Models\Reservation;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * In-memory provider for tests and demos. It does NOT model any real company's API.
 *
 * Webhook format (fake, our own design):
 *   headers  X-Fake-Timestamp: <unix seconds>
 *            X-Fake-Signature: sha256=<hex HMAC-SHA256 of "<timestamp>.<raw body>" with RESERVATION_WEBHOOK_SECRET>
 *   body     {"id","customer_name","customer_phone","party_size","reserved_for","status","note","table_number"}
 */
class FakeProvider implements ReservationProvider
{
    public const SIGNATURE_HEADER = 'X-Fake-Signature';

    public const TIMESTAMP_HEADER = 'X-Fake-Timestamp';

    /** Webhooks older than this (or this far in the future) are rejected to limit replays. */
    public const TOLERANCE_SECONDS = 300;

    /** @var list<ExternalReservationData>|null test override for fetchReservations() */
    public static ?array $remoteReservations = null;

    /** @var list<array{action: string, reservation_id: int, external_id: ?string, status: ?string}> */
    public static array $calls = [];

    public static bool $failConnection = false;

    public static ?string $failPushWith = null;

    public static function reset(): void
    {
        static::$remoteReservations = null;
        static::$calls = [];
        static::$failConnection = false;
        static::$failPushWith = null;
    }

    public function __construct(private readonly ?string $webhookSecret) {}

    public function name(): string
    {
        return 'fake';
    }

    public function label(): string
    {
        return 'Demo sağlayıcı (fake)';
    }

    public function supportsWebhooks(): bool
    {
        return true;
    }

    public function supportsPush(): bool
    {
        return true;
    }

    public function testConnection(): bool
    {
        return ! static::$failConnection;
    }

    public function fetchReservations(CarbonInterface $from, CarbonInterface $to): iterable
    {
        $all = static::$remoteReservations ?? $this->demoReservations();

        return array_values(array_filter(
            $all,
            fn (ExternalReservationData $r) => $r->reservedFor->betweenIncluded($from, $to),
        ));
    }

    public function pushReservation(Reservation $reservation): string
    {
        if (static::$failPushWith !== null) {
            throw new RuntimeException(static::$failPushWith);
        }

        $externalId = 'fake-'.Str::lower(Str::random(10));
        static::$calls[] = ['action' => 'push', 'reservation_id' => $reservation->id, 'external_id' => $externalId, 'status' => $reservation->status->value];

        return $externalId;
    }

    public function updateStatus(Reservation $reservation, string $status): void
    {
        if (static::$failPushWith !== null) {
            throw new RuntimeException(static::$failPushWith);
        }

        static::$calls[] = ['action' => 'status', 'reservation_id' => $reservation->id, 'external_id' => $reservation->external_id, 'status' => $status];
    }

    public function verifyWebhook(Request $request): bool
    {
        // Never accept webhooks when no secret is configured.
        if (blank($this->webhookSecret)) {
            return false;
        }

        $timestamp = $request->header(self::TIMESTAMP_HEADER);
        $signature = $request->header(self::SIGNATURE_HEADER);

        if (! is_string($timestamp) || ! ctype_digit($timestamp) || ! is_string($signature)) {
            return false;
        }

        if (abs(now()->getTimestamp() - (int) $timestamp) > self::TOLERANCE_SECONDS) {
            return false;
        }

        return hash_equals(static::sign($request->getContent(), $this->webhookSecret, (int) $timestamp), $signature);
    }

    public function parseWebhook(Request $request): ExternalReservationData
    {
        $data = json_decode($request->getContent(), true);

        if (! is_array($data)
            || ! is_string($data['id'] ?? null) || $data['id'] === '' || strlen($data['id']) > 191
            || ! is_string($data['customer_name'] ?? null) || trim($data['customer_name']) === ''
            || ! is_int($data['party_size'] ?? null) || $data['party_size'] < 1 || $data['party_size'] > 100
            || ! is_string($data['reserved_for'] ?? null)) {
            throw new InvalidProviderPayload('Missing or invalid reservation fields.');
        }

        try {
            $reservedFor = CarbonImmutable::parse($data['reserved_for']);
        } catch (\Throwable) {
            throw new InvalidProviderPayload('Invalid reserved_for.');
        }

        $status = ReservationStatus::tryFrom((string) ($data['status'] ?? 'confirmed'));
        if (! $status) {
            throw new InvalidProviderPayload('Invalid status.');
        }

        return new ExternalReservationData(
            externalId: $data['id'],
            customerName: Str::limit(trim($data['customer_name']), 255, ''),
            partySize: $data['party_size'],
            reservedFor: $reservedFor,
            status: $status,
            customerPhone: is_string($data['customer_phone'] ?? null) ? Str::limit($data['customer_phone'], 32, '') : null,
            note: is_string($data['note'] ?? null) ? Str::limit($data['note'], 1000, '') : null,
            tableNumber: is_int($data['table_number'] ?? null) ? $data['table_number'] : null,
        );
    }

    public static function sign(string $body, string $secret, int $timestamp): string
    {
        return 'sha256='.hash_hmac('sha256', $timestamp.'.'.$body, $secret);
    }

    /** @return list<ExternalReservationData> */
    private function demoReservations(): array
    {
        $today = CarbonImmutable::today();

        return [
            new ExternalReservationData('fake-1001', 'Demo Müşteri (Harici)', 4, $today->addDay()->setTime(19, 0), ReservationStatus::Confirmed, note: 'Pencere kenarı'),
            new ExternalReservationData('fake-1002', 'Kitap Kulübü', 8, $today->addDays(2)->setTime(18, 30), ReservationStatus::Confirmed, tableNumber: 1),
            new ExternalReservationData('fake-1003', 'İptal Edilen Rezervasyon', 2, $today->addDays(3)->setTime(20, 0), ReservationStatus::Cancelled),
        ];
    }
}
