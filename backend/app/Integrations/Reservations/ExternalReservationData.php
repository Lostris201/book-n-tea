<?php

namespace App\Integrations\Reservations;

use App\Enums\ReservationStatus;
use Carbon\CarbonImmutable;

/**
 * Provider-neutral reservation as received from an external system.
 */
final class ExternalReservationData
{
    public function __construct(
        public readonly string $externalId,
        public readonly string $customerName,
        public readonly int $partySize,
        public readonly CarbonImmutable $reservedFor,
        public readonly ReservationStatus $status = ReservationStatus::Confirmed,
        public readonly ?string $customerPhone = null,
        public readonly ?string $note = null,
        public readonly ?int $tableNumber = null,
    ) {}

    /** Serializable form for queued jobs. */
    public function toArray(): array
    {
        return [
            'external_id' => $this->externalId,
            'customer_name' => $this->customerName,
            'party_size' => $this->partySize,
            'reserved_for' => $this->reservedFor->toIso8601String(),
            'status' => $this->status->value,
            'customer_phone' => $this->customerPhone,
            'note' => $this->note,
            'table_number' => $this->tableNumber,
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            externalId: (string) $data['external_id'],
            customerName: (string) $data['customer_name'],
            partySize: (int) $data['party_size'],
            reservedFor: CarbonImmutable::parse($data['reserved_for']),
            status: ReservationStatus::from($data['status']),
            customerPhone: $data['customer_phone'] ?? null,
            note: $data['note'] ?? null,
            tableNumber: isset($data['table_number']) ? (int) $data['table_number'] : null,
        );
    }
}
