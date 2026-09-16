<?php

namespace Tests\Feature\Api;

use App\Enums\OrderStatus;
use App\Enums\UserRole;
use App\Models\CafeTable;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class OrderApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    private function actingAsStaff(): void
    {
        Sanctum::actingAs(User::factory()->role(UserRole::Staff)->create());
    }

    private function placeOrder(array $overrides = []): \Illuminate\Testing\TestResponse
    {
        return $this->postJson('/api/orders', array_merge([
            'table' => '05',
            'items' => [
                ['name' => 'Kütüphane Özel Latte (Yulaf Sütü)', 'price' => 135, 'qty' => 2],
                ['name' => 'San Sebastian Cheesecake', 'price' => 155, 'qty' => 1],
            ],
            'note' => 'Buzsuz lütfen',
        ], $overrides));
    }

    // ---- POST /api/orders ------------------------------------------------------

    public function test_creates_order_with_201(): void
    {
        $response = $this->placeOrder()->assertStatus(201);

        $response->assertJsonPath('table', '05');
        $response->assertJsonPath('status', 'new');
        $response->assertJsonPath('note', 'Buzsuz lütfen');
        $response->assertJsonPath('hasNewItems', false);
        $response->assertJsonPath('total', 425);
        $response->assertJsonPath('items', [
            ['name' => 'Kütüphane Özel Latte (Yulaf Sütü)', 'price' => 135, 'qty' => 2, 'isNew' => false],
            ['name' => 'San Sebastian Cheesecake', 'price' => 155, 'qty' => 1, 'isNew' => false],
        ]);
        $this->assertStringStartsWith('ord_', $response->json('id'));
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}\.\d{3}Z$/', $response->json('createdAt'));

        $order = Order::where('public_id', $response->json('id'))->firstOrFail();
        $this->assertSame(5, $order->table->number);
        $this->assertSame(42500, $order->total_cents);
    }

    public function test_accepts_unpadded_and_numeric_table_numbers(): void
    {
        $this->placeOrder(['table' => '5'])->assertStatus(201)->assertJsonPath('table', '05');
        $this->placeOrder(['table' => 12])->assertStatus(201)->assertJsonPath('table', '12');
    }

    public function test_cleans_items_like_server_js(): void
    {
        $response = $this->placeOrder([
            'items' => [
                ['name' => '  Espresso  ', 'price' => 'abc', 'qty' => 0],
                ['name' => '   ', 'price' => 10, 'qty' => 1],
                ['name' => 'Çay', 'price' => -5, 'qty' => 'x'],
            ],
            'note' => null,
        ])->assertStatus(201);

        $response->assertJsonPath('items', [
            ['name' => 'Espresso', 'price' => 0, 'qty' => 1, 'isNew' => false],
            ['name' => 'Çay', 'price' => 0, 'qty' => 1, 'isNew' => false],
        ]);
        $response->assertJsonPath('note', '');
    }

    public function test_truncates_note_to_200_characters(): void
    {
        $this->placeOrder(['note' => str_repeat('a', 250)])
            ->assertStatus(201)
            ->assertJsonPath('note', str_repeat('a', 200));
    }

    public function test_requires_table_and_items(): void
    {
        $this->placeOrder(['table' => ''])->assertStatus(400)->assertExactJson(['error' => 'Masa ve ürünler gerekli.']);
        $this->placeOrder(['items' => []])->assertStatus(400)->assertExactJson(['error' => 'Masa ve ürünler gerekli.']);
        $this->postJson('/api/orders', [])->assertStatus(400);
    }

    public function test_rejects_items_without_names(): void
    {
        $this->placeOrder(['items' => [['name' => '', 'price' => 10, 'qty' => 1]]])
            ->assertStatus(400)
            ->assertExactJson(['error' => 'Geçerli ürün yok.']);
    }

    public function test_rejects_unknown_or_inactive_table(): void
    {
        $this->placeOrder(['table' => '99'])->assertStatus(404)->assertExactJson(['error' => 'Masa bulunamadı.']);
        $this->placeOrder(['table' => 'abc'])->assertStatus(404);

        CafeTable::where('number', 3)->update(['is_active' => false]);
        $this->placeOrder(['table' => '03'])->assertStatus(404);
    }

    public function test_merges_into_open_order_for_same_table(): void
    {
        $first = $this->placeOrder()->assertStatus(201);
        $id = $first->json('id');

        Order::where('public_id', $id)->update(['status' => OrderStatus::Preparing]);

        $second = $this->placeOrder([
            'items' => [['name' => 'Chai Tea Latte', 'price' => 115, 'qty' => 1]],
            'note' => 'Ekstra peçete',
        ])->assertStatus(200);

        $second->assertJsonPath('id', $id);
        $second->assertJsonPath('status', 'new');
        $second->assertJsonPath('hasNewItems', true);
        $second->assertJsonPath('note', 'Buzsuz lütfen | Ekstra peçete');
        $second->assertJsonPath('total', 540);
        $this->assertSame([false, false, true], array_column($second->json('items'), 'isNew'));

        // A third round demotes the previous "new" items.
        $third = $this->placeOrder([
            'items' => [['name' => 'Double Ristretto Espresso', 'price' => 75, 'qty' => 1]],
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

        $this->placeOrder(['table' => '06'])->assertStatus(201);
        $this->assertSame(3, Order::count());
    }

    // ---- GET /api/orders -------------------------------------------------------

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
        $older = $this->placeOrder(['table' => '07'])->json('id');
        $this->travelBack();

        $this->travelTo(now()->subMinutes(20));
        $oldest = $this->placeOrder(['table' => '02'])->json('id');
        $this->travelBack();

        $done = $this->placeOrder(['table' => '09'])->json('id');
        Order::where('public_id', $done)->update(['status' => OrderStatus::Done]);

        $newest = $this->placeOrder(['table' => '01'])->json('id');

        $this->actingAsStaff();
        $response = $this->getJson('/api/orders')->assertOk();

        $this->assertSame([$oldest, $older, $newest], array_column($response->json(), 'id'));
    }

    public function test_filters_by_status(): void
    {
        $a = $this->placeOrder(['table' => '01'])->json('id');
        $b = $this->placeOrder(['table' => '02'])->json('id');
        Order::where('public_id', $a)->update(['status' => OrderStatus::Done]);
        Order::where('public_id', $b)->update(['status' => OrderStatus::Ready]);

        $this->actingAsStaff();
        $this->assertSame([$a], array_column($this->getJson('/api/orders?status=done')->json(), 'id'));
        $this->assertSame([$b], array_column($this->getJson('/api/orders?status=ready')->json(), 'id'));
        $this->assertSame([], $this->getJson('/api/orders?status=new')->json());
    }

    // ---- PATCH /api/orders/{id} ------------------------------------------------

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
