<?php

namespace App\Services\Legacy;

use App\Models\Category;
use App\Models\Option;
use App\Models\OptionGroup;
use App\Models\Product;
use App\Models\Setting;
use App\Support\Money;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Converts between the database and the legacy admin.js `bnt_admin_data_v1` blob.
 * Transitional: Filament replaces the import side after cutover.
 */
class LegacyMenu
{
    /** settings.key => legacy blob key */
    public const SETTING_KEYS = [
        'cafe_name' => 'cafeName',
        'slogan' => 'slogan',
        'phone' => 'phone',
        'address' => 'address',
        'instagram' => 'instagram',
        'hours' => 'hours',
        'call_waiter_enabled' => 'callWaiter',
        'request_bill_enabled' => 'requestBill',
        'sound_notification' => 'soundNotification',
    ];

    /** @return array<string, mixed> */
    public function export(): array
    {
        $categories = Category::active()->ordered()->get();

        $products = Product::active()
            ->whereIn('category_id', $categories->pluck('id'))
            ->with(['category', 'optionGroups'])
            ->ordered()
            ->get();

        $groups = OptionGroup::with(['options' => fn ($q) => $q->where('is_active', true)])
            ->orderBy('id')
            ->get();

        $mappings = [];
        foreach ($products as $product) {
            if ($product->optionGroups->isEmpty()) {
                continue;
            }
            $enabled = $product->optionGroups->pluck('key')->all();
            foreach ($groups as $group) {
                $mappings[$product->slug][$group->key] = in_array($group->key, $enabled, true);
            }
        }

        $storedSettings = Setting::allAsArray();
        $settings = [];
        foreach (self::SETTING_KEYS as $key => $legacyKey) {
            $settings[$legacyKey] = $storedSettings[$key] ?? null;
        }

        return [
            'products' => $products->map(fn (Product $p) => [
                'id' => $p->slug,
                'name' => $p->name,
                'category' => $p->category->slug,
                'price' => Money::toTl($p->price_cents),
                'desc' => $p->description ?? '',
                'image' => $p->image_path,
                'active' => true,
                'bestseller' => $p->is_bestseller,
                'isNew' => $p->is_new,
                'customizable' => $p->optionGroups->isNotEmpty(),
            ])->values()->all(),
            'categories' => $categories->map(fn (Category $c) => [
                'id' => $c->slug,
                'name' => $c->name,
                'icon' => $c->icon,
                'order' => $c->sort_order,
            ])->values()->all(),
            'options' => (object) $groups->mapWithKeys(fn (OptionGroup $g) => [
                $g->key => $g->options->map(fn (Option $o) => [
                    'id' => $o->slug,
                    'name' => $o->name,
                    'price' => Money::toTl($o->price_cents),
                ])->values()->all(),
            ])->all(),
            'productOptionMappings' => (object) $mappings,
            'settings' => $settings,
        ];
    }

    /**
     * Upserts the blob by slug. Anything missing from the blob is deactivated, never deleted,
     * so historical order_items keep their product reference.
     *
     * @param  array<string, mixed>  $data  must contain a `products` list (validated by the caller)
     */
    public function import(array $data): int
    {
        return DB::transaction(function () use ($data) {
            $categoryIds = $this->importCategories($data['categories'] ?? null);
            $groupIds = $this->importOptions($data['options'] ?? null);
            $count = $this->importProducts($data['products'], $categoryIds);

            if (is_array($data['productOptionMappings'] ?? null)) {
                $this->importMappings($data['products'], $data['productOptionMappings'], $groupIds);
            }

            if (is_array($data['settings'] ?? null)) {
                foreach (self::SETTING_KEYS as $key => $legacyKey) {
                    if (array_key_exists($legacyKey, $data['settings'])) {
                        Setting::setValue($key, $data['settings'][$legacyKey]);
                    }
                }
            }

            return $count;
        });
    }

    /** @return array<string, int> slug => id */
    private function importCategories(mixed $categories): array
    {
        if (is_array($categories)) {
            $seen = [];
            foreach (array_values($categories) as $i => $c) {
                $slug = $this->slug($c['id'] ?? null);
                if (! $slug || ! filled($c['name'] ?? null)) {
                    continue;
                }
                Category::updateOrCreate(['slug' => $slug], [
                    'name' => Str::limit((string) $c['name'], 255, ''),
                    'icon' => isset($c['icon']) ? Str::limit((string) $c['icon'], 255, '') : null,
                    'sort_order' => is_numeric($c['order'] ?? null) ? (int) $c['order'] : $i + 1,
                    'is_active' => true,
                ]);
                $seen[] = $slug;
            }
            Category::whereNotIn('slug', $seen)->update(['is_active' => false]);
        }

        return Category::pluck('id', 'slug')->all();
    }

    /** @return array<string, int> group key => id */
    private function importOptions(mixed $options): array
    {
        if (is_array($options)) {
            foreach ($options as $key => $list) {
                $key = $this->slug($key);
                if (! $key || ! is_array($list)) {
                    continue;
                }
                $group = OptionGroup::firstOrCreate(['key' => $key], [
                    'name' => Str::headline($key),
                    'is_multi_select' => $key === 'extras',
                ]);

                $seen = [];
                foreach (array_values($list) as $i => $o) {
                    if (! filled($o['name'] ?? null)) {
                        continue;
                    }
                    $slug = $this->slug($o['id'] ?? null) ?? $key.'_'.Str::lower(Str::random(8));
                    Option::updateOrCreate(['slug' => $slug], [
                        'option_group_id' => $group->id,
                        'name' => Str::limit((string) $o['name'], 255, ''),
                        'price_cents' => Money::fromTl($o['price'] ?? 0),
                        'sort_order' => $i + 1,
                        'is_active' => true,
                    ]);
                    $seen[] = $slug;
                }
                $group->options()->whereNotIn('slug', $seen)->update(['is_active' => false]);
            }
        }

        return OptionGroup::pluck('id', 'key')->all();
    }

    /** @param  array<string, int>  $categoryIds */
    private function importProducts(array $products, array $categoryIds): int
    {
        $seen = [];
        foreach (array_values($products) as $i => $p) {
            $slug = $this->slug($p['id'] ?? null);
            $categorySlug = $this->slug($p['category'] ?? null);
            if (! $slug || ! filled($p['name'] ?? null) || ! $categorySlug) {
                continue;
            }

            if (! isset($categoryIds[$categorySlug])) {
                $categoryIds[$categorySlug] = Category::create([
                    'slug' => $categorySlug,
                    'name' => Str::headline($categorySlug),
                    'sort_order' => count($categoryIds) + 1,
                ])->id;
            }

            $image = isset($p['image']) && is_string($p['image']) && strlen($p['image']) <= 255 ? $p['image'] : null;

            Product::updateOrCreate(['slug' => $slug], [
                'category_id' => $categoryIds[$categorySlug],
                'name' => Str::limit((string) $p['name'], 255, ''),
                'description' => isset($p['desc']) ? (string) $p['desc'] : null,
                'image_path' => $image,
                'price_cents' => Money::fromTl($p['price'] ?? 0),
                'is_active' => ($p['active'] ?? true) !== false,
                'is_bestseller' => (bool) ($p['bestseller'] ?? false),
                'is_new' => (bool) ($p['isNew'] ?? false),
                'sort_order' => $i + 1,
            ]);
            $seen[] = $slug;
        }

        Product::whereNotIn('slug', $seen)->update(['is_active' => false]);

        return count($seen);
    }

    /** @param  array<string, int>  $groupIds */
    private function importMappings(array $products, array $mappings, array $groupIds): void
    {
        foreach ($products as $p) {
            $slug = $this->slug($p['id'] ?? null);
            $product = $slug ? Product::where('slug', $slug)->first() : null;
            if (! $product) {
                continue;
            }

            $flags = is_array($mappings[$slug] ?? null) ? $mappings[$slug] : [];
            $ids = [];
            foreach ($flags as $key => $enabled) {
                if ($enabled === true && isset($groupIds[$key])) {
                    $ids[] = $groupIds[$key];
                }
            }
            $product->optionGroups()->sync($ids);
        }
    }

    private function slug(mixed $value): ?string
    {
        if (! is_string($value) && ! is_int($value)) {
            return null;
        }
        $value = trim((string) $value);

        return $value === '' || strlen($value) > 255 ? null : $value;
    }
}
