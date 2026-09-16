<?php

namespace App\Services\Orders;

use App\Enums\OrderStatus;
use App\Models\CafeTable;
use App\Models\Order;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class OrderService
{
    public const NOTE_PART_MAX = 200;

    public const NOTE_MAX = 1000;

    /**
     * Merge rule: if the table has an open order, demote its items (is_new=false), append the new
     * items with is_new=true, join notes with " | ", reset status to new and flag has_new_items.
     *
     * @param  list<array{product_id: int, name: string, options: array, unit_price_cents: int, qty: int}>  $lines  priced by OrderPricer
     * @return array{0: Order, 1: bool} [order, created]
     */
    public function place(CafeTable $table, array $lines, string $note): array
    {
        return DB::transaction(function () use ($table, $lines, $note) {
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

            $order->items()->createMany(array_map(fn (array $line) => [
                'product_id' => $line['product_id'],
                'name_snapshot' => $line['name'],
                'options_snapshot' => $line['options'],
                'unit_price_cents' => $line['unit_price_cents'],
                'qty' => $line['qty'],
                'is_new' => ! $created,
            ], $lines));

            $order->recalculateTotal();
            $order->save();

            return [$order->fresh(['table', 'items']), $created];
        });
    }

    public function cleanNote(mixed $note): string
    {
        return is_scalar($note) ? Str::limit(trim((string) $note), self::NOTE_PART_MAX, '') : '';
    }
}
