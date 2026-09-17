<?php

namespace Tests\Feature\Integrations;

use App\Enums\UserRole;
use App\Filament\Pages\ManageIntegrations;
use App\Integrations\Reservations\Providers\FakeProvider;
use App\Models\Integration;
use App\Models\Reservation;
use App\Models\User;
use Filament\Facades\Filament;
use Livewire\Livewire;
use Spatie\Activitylog\Models\Activity;

class IntegrationsPageTest extends IntegrationTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Filament::setCurrentPanel('admin');
    }

    public function test_only_admins_can_open_the_page(): void
    {
        $this->actingAs(User::factory()->role(UserRole::Manager)->create());
        $this->get('/admin/integrations')->assertForbidden();

        $this->actingAs(User::factory()->role(UserRole::Admin)->create());
        $this->get('/admin/integrations')->assertOk();
    }

    public function test_page_shows_status_but_never_secret_values(): void
    {
        config(['services.reservation.base_url' => 'https://api.provider.test/v1?token=abc']);
        $this->actingAs(User::factory()->role(UserRole::Admin)->create());

        $response = $this->get('/admin/integrations')->assertOk();

        $response->assertSee('Demo sağlayıcı (fake)');
        $response->assertSee('Ayarlı');
        $response->assertSee('api.provider.test');
        $response->assertSee('/api/webhooks/reservations/fake');
        $response->assertDontSee('live_key_should_never_leak');
        $response->assertDontSee(self::WEBHOOK_SECRET);
        $response->assertDontSee('token=abc');
    }

    public function test_admin_enables_tests_and_syncs(): void
    {
        $admin = User::factory()->role(UserRole::Admin)->create();
        $this->actingAs($admin);

        Livewire::test(ManageIntegrations::class)
            ->assertActionVisible('enable')
            ->assertActionHidden('syncNow')
            ->callAction('enable')
            ->assertNotified('Entegrasyon etkinleştirildi.');

        $this->assertTrue(Integration::where('provider', 'fake')->value('is_enabled'));
        $this->assertTrue(Activity::where('subject_type', Integration::class)->get()->contains(fn ($a) => $a->causer?->is($admin)));

        Livewire::test(ManageIntegrations::class)
            ->callAction('testConnection')
            ->assertNotified('Bağlantı başarılı.')
            ->callAction('syncNow')
            ->assertNotified('3 rezervasyon senkronize edildi.');

        $this->assertSame(3, Reservation::count());

        FakeProvider::$failConnection = true;
        $security = $this->captureLog('security');
        Livewire::test(ManageIntegrations::class)
            ->callAction('testConnection')
            ->assertNotified('Bağlantı kurulamadı.');
        $this->assertTrue($security->hasWarningThatContains('Integration connection test failed'));

        Livewire::test(ManageIntegrations::class)->callAction('disable');
        $this->assertFalse(Integration::where('provider', 'fake')->value('is_enabled'));
    }

    public function test_unconfigured_provider_hides_actions(): void
    {
        config(['services.reservation.provider' => '']);
        $this->actingAs(User::factory()->role(UserRole::Admin)->create());

        Livewire::test(ManageIntegrations::class)
            ->assertActionHidden('enable')
            ->assertActionHidden('testConnection')
            ->assertSee('Yapılandırılmamış');
    }
}
