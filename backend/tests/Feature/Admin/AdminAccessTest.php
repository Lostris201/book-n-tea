<?php

namespace Tests\Feature\Admin;

use App\Enums\UserRole;
use App\Models\CafeTable;
use App\Models\Order;

class AdminAccessTest extends AdminTestCase
{
    private const MANAGER_PAGES = [
        '/admin',
        '/admin/products',
        '/admin/products/create',
        '/admin/categories',
        '/admin/option-groups',
        '/admin/cafe-tables',
        '/admin/cafe-tables/qr',
        '/admin/orders',
        '/admin/reservations',
        '/admin/reservations/create',
    ];

    private const ADMIN_ONLY_PAGES = [
        '/admin/users',
        '/admin/users/create',
        '/admin/activities',
        '/admin/settings',
    ];

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get('/admin')->assertRedirect('/admin/login');
        $this->get('/admin/products')->assertRedirect('/admin/login');
    }

    public function test_staff_cannot_open_the_admin_panel(): void
    {
        $this->actingAsRole(UserRole::Staff);

        foreach ([...self::MANAGER_PAGES, ...self::ADMIN_ONLY_PAGES] as $url) {
            $this->get($url)->assertForbidden();
        }
    }

    public function test_inactive_manager_cannot_open_the_admin_panel(): void
    {
        $this->actingAsRole(UserRole::Manager, ['is_active' => false]);

        $this->get('/admin')->assertForbidden();
        $this->get('/admin/products')->assertForbidden();
    }

    public function test_manager_can_manage_menu_tables_orders_and_reservations_only(): void
    {
        $this->actingAsRole(UserRole::Manager);

        foreach (self::MANAGER_PAGES as $url) {
            $this->get($url)->assertOk();
        }
        foreach (self::ADMIN_ONLY_PAGES as $url) {
            $this->get($url)->assertForbidden();
        }
    }

    public function test_admin_can_open_everything(): void
    {
        $admin = $this->actingAsRole(UserRole::Admin);

        foreach ([...self::MANAGER_PAGES, ...self::ADMIN_ONLY_PAGES] as $url) {
            $this->get($url)->assertOk();
        }

        $this->get("/admin/users/{$admin->id}/edit")->assertOk();
    }

    public function test_detail_pages_render(): void
    {
        $this->actingAsRole(UserRole::Manager);

        $order = Order::create(['cafe_table_id' => CafeTable::first()->id]);
        $order->items()->create(['name_snapshot' => 'Latte', 'options_snapshot' => [['id' => 'm_3', 'group' => 'milk', 'name' => 'Yulaf Sütü', 'price_cents' => 1500]], 'unit_price_cents' => 13500, 'qty' => 2]);
        $order->recalculateTotal();
        $order->save();

        $this->get("/admin/orders/{$order->getRouteKey()}")->assertOk()->assertSee('Latte')->assertSee('Yulaf Sütü')->assertSee('270,00');
        $this->get('/admin/products/1/edit')->assertOk();
        $this->get('/admin/option-groups/1/edit')->assertOk();
        $this->get('/admin/cafe-tables/1/edit')->assertOk();
    }

    public function test_orders_cannot_be_created_or_edited_as_forms(): void
    {
        $this->actingAsRole(UserRole::Admin);

        $this->get('/admin/orders/create')->assertNotFound();
    }
}
