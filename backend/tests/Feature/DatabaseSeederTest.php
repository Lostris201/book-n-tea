<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Models\CafeTable;
use App\Models\Category;
use App\Models\Integration;
use App\Models\Option;
use App\Models\OptionGroup;
use App\Models\Order;
use App\Models\Product;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DatabaseSeederTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    public function test_seeds_twelve_active_tables_with_unique_tokens(): void
    {
        $tables = CafeTable::orderBy('number')->get();

        $this->assertCount(12, $tables);
        $this->assertSame(range(1, 12), $tables->pluck('number')->all());
        $this->assertSame('Masa 01', $tables->first()->name);
        $this->assertTrue($tables->every(fn ($t) => $t->is_active && strlen($t->qr_token) === 32));
        $this->assertCount(12, $tables->pluck('qr_token')->unique());
    }

    public function test_seeds_menu_with_prices_in_cents(): void
    {
        $this->assertSame(6, Category::count());
        $this->assertSame(18, Product::count());
        $this->assertSame(3, OptionGroup::count());
        $this->assertSame(12, Option::count());

        $latte = Product::where('slug', 'coff_1')->firstOrFail();
        $this->assertSame(12000, $latte->price_cents);
        $this->assertSame('coffee', $latte->category->slug);
        $this->assertSame(1500, Option::where('slug', 'm_3')->value('price_cents'));
    }

    public function test_seeds_product_option_mappings(): void
    {
        $keys = fn (string $slug) => Product::where('slug', $slug)->firstOrFail()
            ->optionGroups->pluck('key')->sort()->values()->all();

        $this->assertSame(['extras', 'milk', 'sugar'], $keys('coff_1'));
        $this->assertSame(['extras', 'milk', 'sugar'], $keys('coff_3'));
        $this->assertSame(['milk', 'sugar'], $keys('tea_1'));
        $this->assertSame(['extras', 'milk', 'sugar'], $keys('tea_4'));
        $this->assertSame([], $keys('tea_2'));
        $this->assertTrue(OptionGroup::where('key', 'extras')->value('is_multi_select'));
    }

    public function test_seeds_settings(): void
    {
        $this->assertSame('Book N Tea', Setting::getValue('cafe_name'));
        $this->assertTrue(Setting::getValue('call_waiter_enabled'));
        $this->assertCount(9, Setting::allAsArray());
    }

    public function test_reseeding_is_idempotent_and_keeps_qr_tokens(): void
    {
        $tokens = CafeTable::orderBy('number')->pluck('qr_token')->all();

        $this->seed();

        $this->assertSame(18, Product::count());
        $this->assertSame(12, Option::count());
        $this->assertSame($tokens, CafeTable::orderBy('number')->pluck('qr_token')->all());
    }

    public function test_dev_users_are_seeded_outside_production(): void
    {
        $this->assertSame(['admin', 'manager', 'staff'],
            User::orderBy('email')->get()->map(fn ($u) => $u->role->value)->all());
    }

    public function test_order_generates_public_id_and_total(): void
    {
        $order = Order::create(['cafe_table_id' => CafeTable::first()->id]);
        $order->items()->createMany([
            ['name_snapshot' => 'Latte', 'unit_price_cents' => 12000, 'qty' => 2],
            ['name_snapshot' => 'Espresso', 'unit_price_cents' => 7500, 'qty' => 1, 'options_snapshot' => ['Yulaf Sütü']],
        ]);
        $order->recalculateTotal();
        $order->save();

        $order->refresh();
        $this->assertStringStartsWith('ord_', $order->public_id);
        $this->assertSame(OrderStatus::New, $order->status);
        $this->assertSame(31500, $order->total_cents);
        $this->assertSame(['Yulaf Sütü'], $order->items[1]->options_snapshot);
        $this->assertSame(1, Order::open()->count());
    }

    public function test_integration_config_is_encrypted_at_rest(): void
    {
        Integration::create(['provider' => 'fake', 'config' => ['api_key' => 'secret-value']]);

        $raw = DB::table('integrations')->where('provider', 'fake')->value('config');
        $this->assertStringNotContainsString('secret-value', $raw);
        $this->assertSame('secret-value', Integration::where('provider', 'fake')->first()->config['api_key']);
    }
}
