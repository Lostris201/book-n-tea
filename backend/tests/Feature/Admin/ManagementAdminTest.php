<?php

namespace Tests\Feature\Admin;

use App\Enums\UserRole;
use App\Filament\Pages\ManageSettings;
use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Models\Integration;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Spatie\Activitylog\Models\Activity;

class ManagementAdminTest extends AdminTestCase
{
    public function test_admin_updates_settings_and_api_reflects_them(): void
    {
        $admin = $this->actingAsRole(UserRole::Admin);

        Livewire::test(ManageSettings::class)
            ->assertSchemaStateSet(['cafe_name' => 'Book N Tea', 'call_waiter_enabled' => true])
            ->fillForm(['cafe_name' => 'Book n Tea Moda', 'request_bill_enabled' => false])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('Book n Tea Moda', Setting::getValue('cafe_name'));
        $this->assertFalse(Setting::getValue('request_bill_enabled'));
        $this->getJson('/api/menu')->assertJsonPath('settings.cafeName', 'Book n Tea Moda')->assertJsonPath('settings.requestBill', false);

        $activity = Activity::where('subject_type', Setting::class)->where('event', 'updated')->latest('id')->firstOrFail();
        $this->assertTrue($activity->causer->is($admin));
    }

    public function test_manager_cannot_save_settings(): void
    {
        $this->actingAsRole(UserRole::Manager);

        Livewire::test(ManageSettings::class)->assertForbidden();
    }

    public function test_admin_creates_user_with_hashed_password(): void
    {
        $this->actingAsRole(UserRole::Admin);

        Livewire::test(CreateUser::class)
            ->fillForm([
                'name' => 'Yeni Garson',
                'email' => 'garson@bookntea.test',
                'role' => 'staff',
                'is_active' => true,
                'password' => 'cok-gizli-sifre',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $user = User::where('email', 'garson@bookntea.test')->firstOrFail();
        $this->assertSame(UserRole::Staff, $user->role);
        $this->assertTrue(Hash::check('cok-gizli-sifre', $user->password));

        // Audit log never contains the password or its hash.
        $json = Activity::where('subject_type', User::class)->where('subject_id', $user->id)->get()->toJson();
        $this->assertStringNotContainsString('password', $json);
        $this->assertStringNotContainsString($user->password, $json);
    }

    public function test_short_passwords_are_rejected(): void
    {
        $this->actingAsRole(UserRole::Admin);

        Livewire::test(CreateUser::class)
            ->fillForm(['name' => 'X', 'email' => 'x@bookntea.test', 'role' => 'staff', 'password' => 'short'])
            ->call('create')
            ->assertHasFormErrors(['password']);
    }

    public function test_editing_without_password_keeps_it_and_deactivation_revokes_api_tokens(): void
    {
        $this->actingAsRole(UserRole::Admin);
        $staff = $this->userWithRole(UserRole::Staff);
        $originalHash = $staff->password;
        $staff->createToken('api');

        Livewire::test(EditUser::class, ['record' => $staff->getRouteKey()])
            ->fillForm(['is_active' => false, 'password' => ''])
            ->call('save')
            ->assertHasNoFormErrors();

        $staff->refresh();
        $this->assertFalse($staff->is_active);
        $this->assertSame($originalHash, $staff->password);
        $this->assertSame(0, $staff->tokens()->count());
    }

    public function test_admin_cannot_demote_deactivate_or_delete_self(): void
    {
        $admin = $this->actingAsRole(UserRole::Admin);

        Livewire::test(EditUser::class, ['record' => $admin->getRouteKey()])
            ->fillForm(['role' => 'staff', 'is_active' => false])
            ->call('save');

        $admin->refresh();
        $this->assertSame(UserRole::Admin, $admin->role);
        $this->assertTrue($admin->is_active);
        $this->assertFalse($admin->can('delete', $admin));
    }

    public function test_integration_config_changes_are_audited_without_values(): void
    {
        $admin = $this->actingAsRole(UserRole::Admin);

        $integration = Integration::create(['provider' => 'fake', 'is_enabled' => false, 'config' => ['api_key' => 'super-secret-key']]);
        $integration->update(['config' => ['api_key' => 'rotated-secret-key']]);

        $activities = Activity::where('subject_type', Integration::class)->get();
        $this->assertTrue($activities->contains(fn ($a) => ($a->properties['config_changed'] ?? false) === true));
        $this->assertTrue($activities->every(fn ($a) => $a->causer?->is($admin)));

        $json = $activities->toJson();
        $this->assertStringNotContainsString('super-secret-key', $json);
        $this->assertStringNotContainsString('rotated-secret-key', $json);
        $this->assertStringNotContainsString('api_key', $json);
    }
}
