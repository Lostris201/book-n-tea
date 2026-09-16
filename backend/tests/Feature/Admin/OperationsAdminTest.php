<?php

namespace Tests\Feature\Admin;

use App\Enums\OrderStatus;
use App\Enums\ReservationSource;
use App\Enums\ReservationStatus;
use App\Enums\UserRole;
use App\Filament\Resources\CafeTables\Pages\ListCafeTables;
use App\Filament\Resources\Orders\Pages\ViewOrder;
use App\Filament\Resources\Reservations\Pages\CreateReservation;
use App\Filament\Resources\Reservations\Pages\EditReservation;
use App\Filament\Widgets\CafeStatsOverview;
use App\Models\CafeTable;
use App\Models\Order;
use App\Models\Reservation;
use App\Models\WaiterCall;
use Filament\Actions\Testing\TestAction;
use Livewire\Livewire;
use Spatie\Activitylog\Models\Activity;

class OperationsAdminTest extends AdminTestCase
{
    public function test_regenerating_a_table_token_invalidates_the_old_qr_and_is_audited_without_the_token(): void
    {
        $manager = $this->actingAsRole(UserRole::Manager);
        $table = CafeTable::where('number', 3)->firstOrFail();
        $oldToken = $table->qr_token;

        Livewire::test(ListCafeTables::class)
            ->callAction(TestAction::make('regenerateToken')->table($table))
            ->assertNotified();

        $table->refresh();
        $this->assertNotSame($oldToken, $table->qr_token);
        $this->assertSame(32, strlen($table->qr_token));

        $this->getJson('/api/tables/resolve?t='.$oldToken)->assertNotFound();
        $this->getJson('/api/tables/resolve?t='.$table->qr_token)->assertOk();

        $activity = Activity::where('subject_type', CafeTable::class)->where('subject_id', $table->id)->latest('id')->firstOrFail();
        $this->assertTrue($activity->causer->is($manager));
        $this->assertTrue($activity->properties['qr_token_regenerated']);
        $this->assertStringNotContainsString($table->qr_token, Activity::all()->toJson());
        $this->assertStringNotContainsString($oldToken, Activity::all()->toJson());
    }

    public function test_qr_print_page_contains_token_links_and_svgs(): void
    {
        config(['services.frontend.qr_menu_url' => 'https://menu.bookntea.test/']);
        $this->actingAsRole(UserRole::Manager);
        CafeTable::where('number', 12)->update(['is_active' => false]);

        $response = $this->get('/admin/cafe-tables/qr')->assertOk();

        $response->assertSee('Masa 01');
        $response->assertDontSee('Masa 12');
        $response->assertSee('<svg', false);
        $response->assertDontSee('QR_MENU_URL ayarlanmamış');
        $this->assertSame(11, substr_count($response->getContent(), 'class="bnt-qr-card"'));
    }

    public function test_order_status_can_be_changed_from_the_panel(): void
    {
        $this->actingAsRole(UserRole::Manager);
        $order = Order::create(['cafe_table_id' => CafeTable::first()->id]);

        Livewire::test(ViewOrder::class, ['record' => $order->getRouteKey()])
            ->callAction('changeStatus', data: ['status' => 'done'])
            ->assertHasNoFormErrors();

        $order->refresh();
        $this->assertSame(OrderStatus::Done, $order->status);
        $this->assertNotNull($order->closed_at);
    }

    public function test_manager_creates_native_reservation(): void
    {
        $this->actingAsRole(UserRole::Manager);
        $table = CafeTable::where('number', 4)->firstOrFail();

        Livewire::test(CreateReservation::class)
            ->fillForm([
                'customer_name' => 'Ayşe Yılmaz',
                'customer_phone' => '+90 532 000 00 00',
                'reserved_for' => now()->addDay()->setTime(19, 30)->format('Y-m-d H:i:s'),
                'party_size' => 4,
                'cafe_table_id' => $table->id,
                'status' => 'confirmed',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $reservation = Reservation::firstOrFail();
        $this->assertSame(ReservationSource::Native, $reservation->source);
        $this->assertSame(ReservationStatus::Confirmed, $reservation->status);
        $this->assertTrue($reservation->table->is($table));

        // Phone numbers stay out of the audit log.
        $activity = Activity::where('subject_type', Reservation::class)->firstOrFail();
        $this->assertArrayNotHasKey('customer_phone', $activity->properties['attributes']);
        $this->assertSame('Ayşe Yılmaz', $activity->properties['attributes']['customer_name']);
    }

    public function test_external_reservation_details_are_read_only(): void
    {
        $this->actingAsRole(UserRole::Manager);
        $reservation = Reservation::create([
            'source' => ReservationSource::External,
            'external_provider' => 'fake',
            'external_id' => 'ext-1',
            'customer_name' => 'Harici Müşteri',
            'party_size' => 2,
            'reserved_for' => now()->addDay(),
        ]);

        Livewire::test(EditReservation::class, ['record' => $reservation->getRouteKey()])
            ->fillForm(['customer_name' => 'Değiştirilmiş', 'party_size' => 9, 'status' => 'seated'])
            ->call('save')
            ->assertHasNoFormErrors();

        $reservation->refresh();
        $this->assertSame('Harici Müşteri', $reservation->customer_name);
        $this->assertSame(2, $reservation->party_size);
        $this->assertSame(ReservationStatus::Seated, $reservation->status);
    }

    public function test_dashboard_stats(): void
    {
        $this->actingAsRole(UserRole::Manager);
        $table = CafeTable::first();

        Order::create(['cafe_table_id' => $table->id, 'total_cents' => 12000]);
        Order::create(['cafe_table_id' => $table->id, 'total_cents' => 25050, 'status' => OrderStatus::Done, 'closed_at' => now()]);
        Reservation::create(['customer_name' => 'A', 'party_size' => 2, 'reserved_for' => now()->setTime(20, 0)]);
        Reservation::create(['customer_name' => 'B', 'party_size' => 2, 'reserved_for' => now()->setTime(21, 0), 'status' => ReservationStatus::Cancelled]);
        WaiterCall::create(['cafe_table_id' => $table->id, 'type' => 'bill']);

        Livewire::test(CafeStatsOverview::class)
            ->assertSee('Açık siparişler')
            ->assertSee('250,50 ₺')
            ->assertSeeInOrder(['Bugünkü siparişler', '2'])
            ->assertSeeInOrder(['Bugünkü rezervasyonlar', '1'])
            ->assertSeeInOrder(['Bekleyen çağrılar', '1']);
    }
}
