<?php

namespace App\Services\Orders;

use App\Models\Order;
use App\Models\OrderItem;
use App\Support\Money;

/**
 * Order JSON for the staff board and the customer receipt (legacy staff.js / script.js shape).
 */
class OrderPresenter
{
    /** @return array<string, mixed> */
    public static function present(Order $order): array
    {
        $order->loadMissing(['table', 'items.product']);

        return [
            'id' => $order->public_id,
            'table' => sprintf('%02d', $order->table->number),
            'tableName' => $order->table->name,
            'items' => $order->items->map(fn (OrderItem $item) => self::item($item))->values()->all(),
            'note' => $order->note,
            'status' => $order->status->value,
            'hasNewItems' => $order->has_new_items,
            'total' => Money::toTl($order->total_cents),
            'createdAt' => self::iso($order->created_at),
            'updatedAt' => self::iso($order->updated_at),
        ];
    }

    /** @return array<string, mixed> */
    private static function item(OrderItem $item): array
    {
        $options = $item->options_snapshot ?? [];
        $optionNames = array_column($options, 'name');

        return [
            'productId' => $item->product?->slug,
            // Legacy UIs render only `name`, so chosen options are folded in: "Latte (Yulaf Sütü, Az Şekerli)".
            'name' => $optionNames ? $item->name_snapshot.' ('.implode(', ', $optionNames).')' : $item->name_snapshot,
            'options' => array_map(fn (array $o) => [
                'id' => $o['id'] ?? null,
                'name' => $o['name'] ?? '',
                'price' => Money::toTl((int) ($o['price_cents'] ?? 0)),
            ], $options),
            'price' => Money::toTl($item->unit_price_cents),
            'qty' => $item->qty,
            'isNew' => $item->is_new,
        ];
    }

    public static function iso(?\DateTimeInterface $date): ?string
    {
        return $date ? \Illuminate\Support\Carbon::instance($date)->utc()->format('Y-m-d\TH:i:s.v\Z') : null;
    }
}
