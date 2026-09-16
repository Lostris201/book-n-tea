<?php

namespace Tests\Feature\Api;

use App\Enums\UserRole;
use App\Models\Category;
use App\Models\Option;
use App\Models\Product;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MenuApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_menu_matches_legacy_admin_blob_shape(): void
    {
        $response = $this->getJson('/api/menu')->assertOk();

        $response->assertJsonStructure([
            'products' => [['id', 'name', 'category', 'price', 'desc', 'image', 'active', 'bestseller', 'isNew', 'customizable']],
            'categories' => [['id', 'name', 'icon', 'order']],
            'options' => ['milk' => [['id', 'name', 'price']], 'sugar', 'extras'],
            'productOptionMappings',
            'settings' => ['cafeName', 'slogan', 'phone', 'address', 'instagram', 'hours', 'callWaiter', 'requestBill', 'soundNotification'],
        ]);

        $response->assertJsonCount(18, 'products');
        $response->assertJsonPath('products.0', [
            'id' => 'tea_1',
            'name' => 'Earl Grey Royal',
            'category' => 'tea',
            'price' => 95,
            'desc' => 'Bergamot harmanlı siyah çay, kurutulmuş peygamber çiçeği ve portakal kabuğu ile demlenmiş kraliyet serisi.',
            'image' => 'assets/earl_grey.png',
            'active' => true,
            'bestseller' => true,
            'isNew' => false,
            'customizable' => true,
        ]);
        $response->assertJsonPath('categories.0', ['id' => 'tea', 'name' => 'Özel Çaylar', 'icon' => '🍵', 'order' => 1]);
        $response->assertJsonPath('options.milk.2', ['id' => 'm_3', 'name' => 'Yulaf Sütü', 'price' => 15]);
        $response->assertJsonPath('productOptionMappings.tea_1', ['milk' => true, 'sugar' => true, 'extras' => false]);
        $response->assertJsonPath('productOptionMappings.coff_1', ['milk' => true, 'sugar' => true, 'extras' => true]);
        $response->assertJsonMissingPath('productOptionMappings.tea_2');
        $response->assertJsonPath('settings.cafeName', 'Book N Tea');
        $response->assertJsonPath('settings.callWaiter', true);
    }

    public function test_menu_returns_fractional_prices_as_decimal_tl(): void
    {
        Product::where('slug', 'tea_2')->update(['price_cents' => 9050]);

        $this->getJson('/api/menu')->assertJsonPath('products.1.price', 90.5);
    }

    public function test_menu_only_includes_active_products_categories_and_options(): void
    {
        Product::where('slug', 'tea_1')->update(['is_active' => false]);
        Category::where('slug', 'books')->update(['is_active' => false]);
        Option::where('slug', 'm_4')->update(['is_active' => false]);

        $response = $this->getJson('/api/menu')->assertOk();

        $productIds = collect($response->json('products'))->pluck('id');
        $this->assertNotContains('tea_1', $productIds);
        $this->assertNotContains('book_1', $productIds);
        $this->assertCount(15, $productIds);
        $this->assertNotContains('books', collect($response->json('categories'))->pluck('id'));
        $this->assertNotContains('m_4', collect($response->json('options.milk'))->pluck('id'));
    }

    public function test_menu_update_requires_authentication(): void
    {
        $this->postJson('/api/menu', ['products' => []])
            ->assertStatus(401)
            ->assertExactJson(['error' => 'Bu işlem için giriş yapmanız gerekiyor.']);
    }

    public function test_menu_update_is_forbidden_for_non_admins(): void
    {
        Sanctum::actingAs(User::factory()->role(UserRole::Staff)->create());
        $this->postJson('/api/menu', ['products' => []])->assertStatus(403)->assertJsonStructure(['error']);

        Sanctum::actingAs(User::factory()->role(UserRole::Manager)->create());
        $this->postJson('/api/menu', ['products' => []])->assertStatus(403);
    }

    public function test_menu_update_rejects_invalid_payload(): void
    {
        Sanctum::actingAs(User::factory()->role(UserRole::Admin)->create());

        $this->postJson('/api/menu', ['foo' => 'bar'])
            ->assertStatus(400)
            ->assertExactJson(['error' => 'Geçersiz menü verisi.']);
    }

    public function test_admin_can_replace_menu_with_legacy_blob(): void
    {
        Sanctum::actingAs(User::factory()->role(UserRole::Admin)->create());

        $blob = $this->getJson('/api/menu')->json();

        // Edit a price, drop a product, add a product, change a mapping, change a setting.
        $blob['products'][0]['price'] = 99.5;
        $blob['products'] = array_values(array_filter($blob['products'], fn ($p) => $p['id'] !== 'book_2'));
        $blob['products'][] = [
            'id' => 'tea_new', 'name' => 'Beyaz Çay', 'category' => 'tea', 'price' => 70,
            'desc' => 'Yeni', 'image' => 'assets/earl_grey.png', 'active' => true, 'bestseller' => false, 'isNew' => true,
        ];
        $blob['productOptionMappings']['tea_1']['extras'] = true;
        $blob['productOptionMappings']['tea_new'] = ['milk' => true, 'sugar' => false, 'extras' => false];
        $blob['options']['milk'][] = ['id' => 'm_5', 'name' => 'Hindistan Cevizi Sütü', 'price' => 20];
        $blob['settings']['cafeName'] = 'Book n Tea Moda';

        $this->postJson('/api/menu', $blob)
            ->assertOk()
            ->assertExactJson(['success' => true, 'count' => 18]);

        $this->assertSame(9950, Product::where('slug', 'tea_1')->value('price_cents'));
        $this->assertFalse(Product::where('slug', 'book_2')->value('is_active'));
        $this->assertSame(7000, Product::where('slug', 'tea_new')->value('price_cents'));
        $this->assertSame(2000, Option::where('slug', 'm_5')->value('price_cents'));
        $this->assertSame('Book n Tea Moda', Setting::getValue('cafe_name'));

        $menu = $this->getJson('/api/menu');
        $menu->assertJsonPath('productOptionMappings.tea_1.extras', true);
        $menu->assertJsonPath('productOptionMappings.tea_new', ['milk' => true, 'sugar' => false, 'extras' => false]);
        $this->assertNotContains('book_2', collect($menu->json('products'))->pluck('id'));
    }
}
