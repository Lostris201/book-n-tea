<?php

namespace App\Services\Orders;

use App\Exceptions\ApiException;
use App\Models\Option;
use App\Models\Product;

/**
 * Turns client lines [{product_id, qty, option_ids}] into priced lines using database prices only.
 * product_id / option_ids are the public ids from GET /api/menu (slugs).
 */
class OrderPricer
{
    public const MAX_LINES = 30;

    public const MAX_QTY = 50;

    /**
     * @return list<array{product_id: int, name: string, options: list<array{id: string, group: string, name: string, price_cents: int}>, unit_price_cents: int, qty: int}>
     */
    public function price(array $items): array
    {
        if (count($items) > self::MAX_LINES) {
            throw new ApiException('Siparişte çok fazla ürün var.', 400);
        }

        $requests = array_map(fn ($item) => $this->parseLine($item), array_values($items));

        $products = Product::with(['category', 'optionGroups'])
            ->whereIn('slug', array_column($requests, 'product_id'))
            ->get()
            ->keyBy('slug');

        $options = Option::with('group')
            ->whereIn('slug', array_merge([], ...array_column($requests, 'option_ids')))
            ->get()
            ->keyBy('slug');

        return array_map(fn (array $line) => $this->priceLine($line, $products, $options), $requests);
    }

    /** @return array{product_id: string, qty: int, option_ids: list<string>} */
    private function parseLine(mixed $item): array
    {
        if (! is_array($item) || ! is_string($item['product_id'] ?? null) || $item['product_id'] === '') {
            throw new ApiException('Geçersiz ürün.', 400);
        }

        $qty = $item['qty'] ?? 1;
        if (! is_int($qty) && ! (is_string($qty) && ctype_digit($qty))) {
            throw new ApiException('Geçersiz ürün adedi.', 400);
        }
        $qty = (int) $qty;
        if ($qty < 1 || $qty > self::MAX_QTY) {
            throw new ApiException('Geçersiz ürün adedi.', 400);
        }

        $optionIds = $item['option_ids'] ?? [];
        if (! is_array($optionIds) || array_filter($optionIds, fn ($id) => ! is_string($id)) !== []) {
            throw new ApiException('Geçersiz seçenek.', 400);
        }

        return [
            'product_id' => $item['product_id'],
            'qty' => $qty,
            'option_ids' => array_values(array_unique($optionIds)),
        ];
    }

    private function priceLine(array $line, $products, $options): array
    {
        /** @var Product|null $product */
        $product = $products->get($line['product_id']);

        if (! $product) {
            throw new ApiException('Ürün bulunamadı.', 422);
        }
        if (! $product->is_active || ! $product->category->is_active) {
            throw new ApiException("“{$product->name}” şu anda sipariş verilemiyor.", 422);
        }

        $allowedGroupIds = $product->optionGroups->pluck('id')->all();
        $chosen = [];
        $perGroup = [];

        foreach ($line['option_ids'] as $optionId) {
            /** @var Option|null $option */
            $option = $options->get($optionId);

            if (! $option || ! $option->is_active || ! in_array($option->option_group_id, $allowedGroupIds, true)) {
                throw new ApiException("“{$product->name}” için geçersiz seçenek.", 422);
            }

            $perGroup[$option->option_group_id] = ($perGroup[$option->option_group_id] ?? 0) + 1;
            if (! $option->group->is_multi_select && $perGroup[$option->option_group_id] > 1) {
                throw new ApiException("{$option->group->name} için yalnızca bir seçim yapılabilir.", 422);
            }

            $chosen[] = [
                'id' => $option->slug,
                'group' => $option->group->key,
                'name' => $option->name,
                'price_cents' => $option->price_cents,
            ];
        }

        return [
            'product_id' => $product->id,
            'name' => $product->name,
            'options' => $chosen,
            'unit_price_cents' => $product->price_cents + array_sum(array_column($chosen, 'price_cents')),
            'qty' => $line['qty'],
        ];
    }
}
