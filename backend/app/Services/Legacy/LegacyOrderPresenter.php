<?php

namespace App\Services\Legacy;

use App\Models\Order;
use App\Models\OrderItem;
use App\Support\Money;

/**
 * Order JSON in the shape legacy staff.js / script.js consume (formerly server.js).
 */
class LegacyOrderPresenter
{
    /** @return array<string, mixed> */
    public static function present(Order $order): array
    {
        $order->loadMissing(['table', 'items']);

        return [
            'id' => $order->public_id,
            'table' => sprintf('%02d', $order->table->number),
            'items' => $order->items->map(fn (OrderItem $item) => [
                'name' => $item->name_snapshot,
                'price' => Money::toTl($item->unit_price_cents),
                'qty' => $item->qty,
                'isNew' => $item->is_new,
            ])->values()->all(),
            'note' => $order->note,
            'status' => $order->status->value,
            'hasNewItems' => $order->has_new_items,
            'total' => Money::toTl($order->total_cents),
            'createdAt' => $order->created_at->utc()->format('Y-m-d\TH:i:s.v\Z'),
            'updatedAt' => $order->updated_at->utc()->format('Y-m-d\TH:i:s.v\Z'),
        ];
    }
}
