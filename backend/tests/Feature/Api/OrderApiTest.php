<?php

namespace Tests\Feature\Api;

use App\Enums\OrderStatus;
use App\Enums\UserRole;
use App\Models\CafeTable;
use App\Models\Category;
use App\Models\Option;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Illuminate\Testing\TestResponse;
use Laravel\Sanctum\Sanctum;
use Mockery;
use Tests\TestCase;

class OrderApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    private function token(int $number = 5): string
    {
        return CafeTable::where('number', $number)->value('qr_token');
    }

    private function actingAsStaff(): void
    {
        Sanctum::actingAs(User::factory()->role(UserRole::Staff)->create());
    }

    private function placeOrder(array $overrides = [], int $table = 5): TestResponse
    {
        return $this->postJson('/api/orders', array_merge([
            'table_token' => $this->token($table),
            'items' => [
                ['product_id' => 'coff_1', 'qty' => 2, 'option_ids' => ['m_3', 's_2']],
                ['product_id' => 'dessert_1', 'qty' => 1],
            ],
            'note' => 'Buzsuz lütfen',
        ], $overrides));
    }

    // ---- POST /api/orders: creation & pricing ------------------------------------

    public function test_creates_order_with_server_side_prices(): void
    {
        $response = $this->placeOrder()->assertStatus(201);

        // Latte 120 + oat milk 15 = 135 × 2, cheesecake 155 → 425
        $response->assertJsonPath('table', '05');
        $response->assertJsonPath('tableName', 'Masa 05');
        $response->assertJsonPath('status', 'new');
        $response->assertJsonPath('note', 'Buzsuz lütfen');
        $response->assertJsonPath('hasNewItems', false);
        $response->assertJsonPath('total', 425);
        $response->assertJsonPath('items.0.name', 'Kütüphane Özel Latte (Yulaf Sütü, Az Şekerli)');
        $response->assertJsonPath('items.0.productId', 'coff_1');
        $response->assertJsonPath('items.0.price', 135);
        $response->assertJsonPath('items.0.qty', 2);
        $response->assertJsonPath('items.0.options', [
            ['id' => 'm_3', 'name' => 'Yulaf Sütü', 'price' => 15],
            ['id' => 's_2', 'name' => 'Az Şekerli', 'price' => 0],
        ]);
        $response->assertJsonPath('items.1', [
            'productId' => 'dessert_1', 'name' => 'San Sebastian Cheesecake', 'options' => [],
            'price' => 155, 'qty' => 1, 'isNew' => false,
        ]);
        $this->assertStringStartsWith('ord_', $response->json('id'));
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}\.\d{3}Z$/', $response->json('createdAt'));

        $order = Order::with('items')->where('public_id', $response->json('id'))->firstOrFail();
        $this->assertSame(5, $order->table->number);
        $this->assertSame(42500, $order->total_cents);
        $this->assertSame('Kütüphane Özel Latte', $order->items[0]->name_snapshot);
        $this->assertSame(13500, $order->items[0]->unit_price_cents);
        $this->assertSame(Product::where('slug', 'coff_1')->value('id'), $order->items[0]->product_id);
        $this->assertSame('milk', $order->items[0]->options_snapshot[0]['group']);
        $this->assertSame(1500, $order->items[0]->options_snapshot[0]['price_cents']);
    }

    public function test_tampered_client_prices_and_names_have_no_effect(): void
    {
        $response = $this->placeOrder([
            'items' => [
                ['product_id' => 'coff_1', 'qty' => 1, 'price' => 0.01, 'unit_price_cents' => 1, 'name' => 'Bedava',
                    'option_ids' => ['m_3'], 'options' => [['id' => 'm_3', 'price' => -100]]],
            ],
            'total' => 1,
            'total_cents' => 1,
        ])->assertStatus(201);

        $response->assertJsonPath('total', 135);
        $response->assertJsonPath('items.0.name', 'Kütüphane Özel Latte (Yulaf Sütü)');
        $this->assertSame(13500, Order::firstOrFail()->total_cents);
    }

    public function test_snapshot_survives_later_menu_edits(): void
    {
        $id = $this->placeOrder()->json('id');

        Product::where('slug', 'coff_1')->update(['name' => 'Yeni İsim', 'price_cents' => 99900]);

        $this->actingAsStaff();
        $this->getJson('/api/orders')
            ->assertJsonPath('0.id', $id)
            ->assertJsonPath('0.items.0.name', 'Kütüphane Özel Latte (Yulaf Sütü, Az Şekerli)')
            ->assertJsonPath('0.total', 425);
    }

    public function test_truncates_note_to_200_characters(): void
    {
        $this->placeOrder(['note' => str_repeat('a', 250)])
            ->assertStatus(201)
            ->assertJsonPath('note', str_repeat('a', 200));
        $this->placeOrder(['note' => ['x']], 6)->assertStatus(201)->assertJsonPath('note', '');
    }

    // ---- POST /api/orders: validation --------------------------------------------

    public function test_requires_table_token_and_items(): void
    {
        $this->placeOrder(['table_token' => ''])->assertStatus(400)->assertExactJson(['error' => 'Masa ve ürünler gerekli.']);
        $this->placeOrder(['items' => []])->assertStatus(400)->assertExactJson(['error' => 'Masa ve ürünler gerekli.']);
        $this->postJson('/api/orders', [])->assertStatus(400);
    }

    public function test_legacy_table_number_payload_is_rejected(): void
    {
        $this->postJson('/api/orders', [
            'table' => '05',
            'items' => [['name' => 'Latte', 'price' => 1, 'qty' => 1]],
        ])->assertStatus(400);

        $this->assertSame(0, Order::count());
    }

    public function test_invalid_table_token_returns_404_and_is_logged(): void
    {
        $channel = Mockery::mock();
        $channel->shouldReceive('warning')->once()->with('Invalid table token', Mockery::on(
            fn (array $context) => ! in_array('not-a-real-token-but-32-chars-xx', $context, true)
        ));
        Log::shouldReceive('channel')->with('security')->andReturn($channel);

        $this->placeOrder(['table_token' => 'not-a-real-token-but-32-chars-xx'])
            ->assertStatus(404)
            ->assertExactJson(['error' => 'Masa bulunamadı. Lütfen masanızdaki QR kodu tekrar okutun.']);
    }

    public function test_inactive_table_token_returns_404(): void
    {
        $token = $this->token(3);
        CafeTable::where('number', 3)->update(['is_active' => false]);

        $this->placeOrder(['table_token' => $token])->assertStatus(404);
    }

    public function test_regenerated_token_invalidates_old_qr(): void
    {
        $old = $this->token(5);
        CafeTable::where('number', 5)->firstOrFail()->regenerateToken();

        $this->placeOrder(['table_token' => $old])->assertStatus(404);
        $this->placeOrder()->assertStatus(201);
    }

    public function test_rejects_unknown_or_inactive_products(): void
    {
        $this->placeOrder(['items' => [['product_id' => 'nope', 'qty' => 1]]])
            ->assertStatus(422)->assertExactJson(['error' => 'Ürün bulunamadı.']);

        Product::where('slug', 'tea_2')->update(['is_active' => false]);
        $this->placeOrder(['items' => [['product_id' => 'tea_2', 'qty' => 1]]])
            ->assertStatus(422)->assertExactJson(['error' => '“Japon Sencha Yeşil Çay” şu anda sipariş verilemiyor.']);

        Category::where('slug', 'books')->update(['is_active' => false]);
        $this->placeOrder(['items' => [['product_id' => 'book_1', 'qty' => 1]]])->assertStatus(422);

        $this->assertSame(0, Order::count());
    }

    public function test_rejects_invalid_options(): void
    {
        // Option group not attached to the product (tea_2 has no options).
        $this->placeOrder(['items' => [['product_id' => 'tea_2', 'qty' => 1, 'option_ids' => ['m_3']]]])
            ->assertStatus(422)->assertExactJson(['error' => '“Japon Sencha Yeşil Çay” için geçersiz seçenek.']);

        // tea_1 has milk+sugar but not extras.
        $this->placeOrder(['items' => [['product_id' => 'tea_1', 'qty' => 1, 'option_ids' => ['e_1']]]])->assertStatus(422);

        // Unknown and inactive options.
        $this->placeOrder(['items' => [['product_id' => 'coff_1', 'qty' => 1, 'option_ids' => ['zzz']]]])->assertStatus(422);
        Option::where('slug', 'm_4')->update(['is_active' => false]);
        $this->placeOrder(['items' => [['product_id' => 'coff_1', 'qty' => 1, 'option_ids' => ['m_4']]]])->assertStatus(422);

        // Two choices from a single-select group.
        $this->placeOrder(['items' => [['product_id' => 'coff_1', 'qty' => 1, 'option_ids' => ['m_1', 'm_3']]]])
            ->assertStatus(422)->assertExactJson(['error' => 'Süt Tercihi için yalnızca bir seçim yapılabilir.']);

        $this->assertSame(0, Order::count());
    }

    public function test_multi_select_extras_are_summed(): void
    {
        $this->placeOrder(['items' => [['product_id' => 'coff_1', 'qty' => 1, 'option_ids' => ['e_1', 'e_2', 'e_2']]]])
            ->assertStatus(201)
            ->assertJsonPath('total', 155); // 120 + 20 + 15 (duplicate id counted once)
    }

    public function test_rejects_bad_quantities_and_shapes(): void
    {
        foreach ([0, -1, 51, 1.5, 'abc', '2x', true] as $qty) {
            $this->placeOrder(['items' => [['product_id' => 'tea_1', 'qty' => $qty]]])
                ->assertStatus(400);
        }

        // Rejected attempts count towards the rate limit too; start a fresh window.
        $this->travel(61)->seconds();

        $this->placeOrder(['items' => [['qty' => 1]]])->assertStatus(400)->assertExactJson(['error' => 'Geçersiz ürün.']);
        $this->placeOrder(['items' => ['coff_1']])->assertStatus(400);
        $this->placeOrder(['items' => [['product_id' => 'coff_1', 'option_ids' => 'm_3']]])->assertStatus(400);
        $this->placeOrder(['items' => array_fill(0, 31, ['product_id' => 'tea_1', 'qty' => 1])])->assertStatus(400);

        $this->assertSame(0, Order::count());
    }

    // ---- POST /api/orders: merge rule --------------------------------------------

    public function test_merges_into_open_order_for_same_table(): void
    {
        $first = $this->placeOrder()->assertStatus(201);
        $id = $first->json('id');

        Order::where('public_id', $id)->update(['status' => OrderStatus::Preparing]);

        $second = $this->placeOrder([
            'items' => [['product_id' => 'tea_4', 'qty' => 1]],
            'note' => 'Ekstra peçete',
        ])->assertStatus(200);

        $second->assertJsonPath('id', $id);
        $second->assertJsonPath('status', 'new');
        $second->assertJsonPath('hasNewItems', true);
        $second->assertJsonPath('note', 'Buzsuz lütfen | Ekstra peçete');
        $second->assertJsonPath('total', 540);
        $this->assertSame([false, false, true], array_column($second->json('items'), 'isNew'));

        $third = $this->placeOrder([
            'items' => [['product_id' => 'coff_2', 'qty' => 1]],
            'note' => '',
        ])->assertStatus(200);

        $this->assertSame([false, false, false, true], array_column($third->json('items'), 'isNew'));
        $third->assertJsonPath('note', 'Buzsuz lütfen | Ekstra peçete');
        $this->assertSame(1, Order::count());
    }

    public function test_merge_uses_new_note_when_existing_note_is_empty(): void
    {
        $this->placeOrder(['note' => ''])->assertStatus(201);

        $this->placeOrder(['note' => 'Sonradan not'])
            ->assertStatus(200)
            ->assertJsonPath('note', 'Sonradan not');
    }

    public function test_does_not_merge_into_done_orders_or_other_tables(): void
    {
        $first = $this->placeOrder()->assertStatus(201);
        Order::where('public_id', $first->json('id'))->update(['status' => OrderStatus::Done]);

        $second = $this->placeOrder()->assertStatus(201);
        $this->assertNotSame($first->json('id'), $second->json('id'));

        $this->placeOrder([], 6)->assertStatus(201);
        $this->assertSame(3, Order::count());
    }

    // ---- POST /api/orders: rate limit --------------------------------------------

    public function test_eleventh_order_in_a_minute_is_rate_limited(): void
    {
        for ($i = 0; $i < 10; $i++) {
            $this->placeOrder(['items' => [['product_id' => 'tea_1', 'qty' => 1]]])->assertSuccessful();
        }

        $this->placeOrder(['items' => [['product_id' => 'tea_1', 'qty' => 1]]])
            ->assertStatus(429)
            ->assertExactJson(['error' => 'Çok fazla istek gönderildi. Lütfen biraz sonra tekrar deneyin.']);

        $this->assertSame(10, Order::firstOrFail()->items()->count());

        // Limit window resets.
        $this->travel(61)->seconds();
        $this->placeOrder(['items' => [['product_id' => 'tea_1', 'qty' => 1]]])->assertSuccessful();
    }

    public function test_rate_limit_is_also_per_table_across_ips(): void
    {
        for ($i = 0; $i < 10; $i++) {
            $this->withServerVariables(['REMOTE_ADDR' => "10.0.0.{$i}"])
                ->placeOrder(['items' => [['product_id' => 'tea_1', 'qty' => 1]]])->assertSuccessful();
        }

        $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.99'])
            ->placeOrder(['items' => [['product_id' => 'tea_1', 'qty' => 1]]])->assertStatus(429);

        // Another table from a fresh IP is unaffected.
        $this->withServerVariables(['REMOTE_ADDR' => '10.0.1.1'])
            ->placeOrder(['items' => [['product_id' => 'tea_1', 'qty' => 1]]], 6)->assertStatus(201);
    }

    // ---- GET /api/orders ---------------------------------------------------------

    public function test_listing_orders_requires_staff_auth(): void
    {
        $this->getJson('/api/orders')
            ->assertStatus(401)
            ->assertExactJson(['error' => 'Bu işlem için giriş yapmanız gerekiyor.']);

        // Legacy fetch() sends no Accept header — must still get JSON 401, not a redirect.
        $this->get('/api/orders')->assertStatus(401)->assertJsonStructure(['error']);
    }

    public function test_lists_open_orders_sorted_oldest_first_by_default(): void
    {
        $this->travelTo(now()->subMinutes(10));
        $older = $this->placeOrder([], 7)->json('id');
        $this->travelBack();

        $this->travelTo(now()->subMinutes(20));
        $oldest = $this->placeOrder([], 2)->json('id');
        $this->travelBack();

        $done = $this->placeOrder([], 9)->json('id');
        Order::where('public_id', $done)->update(['status' => OrderStatus::Done]);

        $newest = $this->placeOrder([], 1)->json('id');

        $this->actingAsStaff();
        $response = $this->getJson('/api/orders')->assertOk();

        $this->assertSame([$oldest, $older, $newest], array_column($response->json(), 'id'));
    }

    public function test_filters_by_status(): void
    {
        $a = $this->placeOrder([], 1)->json('id');
        $b = $this->placeOrder([], 2)->json('id');
        Order::where('public_id', $a)->update(['status' => OrderStatus::Done]);
        Order::where('public_id', $b)->update(['status' => OrderStatus::Ready]);

        $this->actingAsStaff();
        $this->assertSame([$a], array_column($this->getJson('/api/orders?status=done')->json(), 'id'));
        $this->assertSame([$b], array_column($this->getJson('/api/orders?status=ready')->json(), 'id'));
        $this->assertSame([], $this->getJson('/api/orders?status=new')->json());
    }

    // ---- PATCH /api/orders/{id} --------------------------------------------------

    public function test_updating_status_requires_staff_auth(): void
    {
        $id = $this->placeOrder()->json('id');

        $this->patchJson("/api/orders/{$id}", ['status' => 'preparing'])->assertStatus(401);
    }

    public function test_updates_status(): void
    {
        $id = $this->placeOrder()->json('id');
        $this->actingAsStaff();

        $this->patchJson("/api/orders/{$id}", ['status' => 'preparing'])
            ->assertOk()
            ->assertJsonPath('id', $id)
            ->assertJsonPath('status', 'preparing');

        $this->assertNull(Order::where('public_id', $id)->value('closed_at'));
    }

    public function test_done_sets_closed_at(): void
    {
        $id = $this->placeOrder()->json('id');
        $this->actingAsStaff();

        $this->patchJson("/api/orders/{$id}", ['status' => 'done'])->assertOk()->assertJsonPath('status', 'done');

        $this->assertNotNull(Order::where('public_id', $id)->value('closed_at'));
        $this->assertSame([], $this->getJson('/api/orders')->json());
    }

    public function test_rejects_invalid_status_with_400(): void
    {
        $id = $this->placeOrder()->json('id');
        $this->actingAsStaff();

        $this->patchJson("/api/orders/{$id}", ['status' => 'cooking'])
            ->assertStatus(400)
            ->assertExactJson(['error' => 'Geçersiz durum.']);
        $this->patchJson("/api/orders/{$id}", [])->assertStatus(400);
    }

    public function test_unknown_order_returns_404(): void
    {
        $this->actingAsStaff();

        $this->patchJson('/api/orders/ord_missing', ['status' => 'ready'])
            ->assertStatus(404)
            ->assertExactJson(['error' => 'Sipariş bulunamadı.']);
    }

    public function test_inactive_staff_cannot_use_board(): void
    {
        Sanctum::actingAs(User::factory()->role(UserRole::Staff)->create(['is_active' => false]));

        $this->getJson('/api/orders')->assertStatus(403);
    }
}
