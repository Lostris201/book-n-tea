<?php

namespace App\Services\Legacy;

use App\Enums\OrderStatus;
use App\Models\CafeTable;
use App\Models\Order;
use App\Support\Money;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Reproduces server.js POST /api/orders. Phase 3 replaces client-sent prices with server-side pricing.
 */
class LegacyOrderService
{
    public const NOTE_PART_MAX = 200;

    public const NOTE_MAX = 1000;

    public const QTY_MAX = 999;

    /**
     * @param  list<array{name: string, price_cents: int, qty: int}>  $items  already cleaned
     * @return array{0: Order, 1: bool} [order, created]
     */
    public function place(CafeTable $table, array $items, string $note): array
    {
        return DB::transaction(function () use ($table, $items, $note) {
            // Serialize concurrent orders for the same table so they merge instead of duplicating.
            CafeTable::whereKey($table->id)->lockForUpdate()->first();

            $order = Order::where('cafe_table_id', $table->id)
                ->open()
                ->lockForUpdate()
                ->first();

            $created = $order === null;

            if ($created) {
                $order = Order::create([
                    'cafe_table_id' => $table->id,
                    'note' => $note,
                    'status' => OrderStatus::New,
                ]);
            } else {
                $order->items()->update(['is_new' => false]);

                if ($note !== '') {
                    $order->note = Str::limit($order->note !== '' ? "{$order->note} | {$note}" : $note, self::NOTE_MAX, '');
                }
                $order->status = OrderStatus::New;
                $order->has_new_items = true;
            }

            $order->items()->createMany(array_map(fn (array $item) => [
                'name_snapshot' => $item['name'],
                'unit_price_cents' => $item['price_cents'],
                'qty' => $item['qty'],
                'is_new' => ! $created,
            ], $items));

            $order->recalculateTotal();
            $order->save();

            return [$order->fresh(['table', 'items']), $created];
        });
    }

    /**
     * server.js cleaning rules: trimmed name required, price Number()||0, qty max(1, Number()||1).
     *
     * @return list<array{name: string, price_cents: int, qty: int}>
     */
    public function cleanItems(array $items): array
    {
        $clean = [];
        foreach ($items as $item) {
            if (! is_array($item)) {
                continue;
            }
            $name = is_scalar($item['name'] ?? null) ? trim((string) $item['name']) : '';
            if ($name === '') {
                continue;
            }
            $qty = is_numeric($item['qty'] ?? null) ? (int) $item['qty'] : 1;

            $clean[] = [
                'name' => Str::limit($name, 255, ''),
                'price_cents' => Money::fromTl($item['price'] ?? 0),
                'qty' => min(self::QTY_MAX, max(1, $qty)),
            ];
        }

        return $clean;
    }

    public function cleanNote(mixed $note): string
    {
        return is_scalar($note) ? Str::limit(trim((string) $note), self::NOTE_PART_MAX, '') : '';
    }

    /** Legacy sends the table number as a string, often zero-padded ("05"). */
    public function findTable(mixed $table): ?CafeTable
    {
        $value = is_scalar($table) ? trim((string) $table) : '';
        if (! ctype_digit($value)) {
            return null;
        }

        return CafeTable::active()->where('number', (int) $value)->first();
    }
}
