<?php

namespace Tests\Feature\Admin;

use App\Enums\UserRole;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

abstract class AdminTestCase extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        Filament::setCurrentPanel('admin');
    }

    protected function userWithRole(UserRole $role, array $attributes = []): User
    {
        return User::factory()->role($role)->create($attributes);
    }

    protected function actingAsRole(UserRole $role, array $attributes = []): User
    {
        $user = $this->userWithRole($role, $attributes);
        $this->actingAs($user);

        return $user;
    }
}
