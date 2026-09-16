<?php

namespace Tests\Feature\Api;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_returns_token_that_authorizes_staff_endpoints(): void
    {
        User::factory()->role(UserRole::Staff)->create(['email' => 'garson@example.test']);

        $response = $this->postJson('/api/auth/login', ['email' => 'garson@example.test', 'password' => 'password'])
            ->assertOk()
            ->assertJsonPath('user.role', 'staff');

        $token = $response->json('token');
        $this->assertNotEmpty($token);

        $this->withToken($token)->getJson('/api/orders')->assertOk();
        $this->withToken($token)->getJson('/api/auth/me')->assertJsonPath('user.email', 'garson@example.test');
    }

    public function test_logout_revokes_token(): void
    {
        User::factory()->create(['email' => 'garson@example.test']);
        $token = $this->postJson('/api/auth/login', ['email' => 'garson@example.test', 'password' => 'password'])->json('token');

        $this->withToken($token)->postJson('/api/auth/logout')->assertOk();

        $this->app['auth']->forgetGuards();
        $this->withToken($token)->getJson('/api/orders')->assertStatus(401);
    }

    public function test_wrong_credentials_are_rejected(): void
    {
        User::factory()->create(['email' => 'garson@example.test']);

        $this->postJson('/api/auth/login', ['email' => 'garson@example.test', 'password' => 'wrong'])
            ->assertStatus(401)
            ->assertExactJson(['error' => 'E-posta veya şifre hatalı.']);
        $this->postJson('/api/auth/login', ['email' => 'nobody@example.test', 'password' => 'password'])
            ->assertStatus(401);
        $this->postJson('/api/auth/login', [])->assertStatus(400);
    }

    public function test_inactive_users_cannot_log_in(): void
    {
        User::factory()->create(['email' => 'eski@example.test', 'is_active' => false]);

        $this->postJson('/api/auth/login', ['email' => 'eski@example.test', 'password' => 'password'])
            ->assertStatus(403);
    }

    public function test_login_is_rate_limited(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/auth/login', ['email' => 'x@example.test', 'password' => 'bad'])->assertStatus(401);
        }

        $this->postJson('/api/auth/login', ['email' => 'x@example.test', 'password' => 'bad'])
            ->assertStatus(429)
            ->assertJsonStructure(['error']);
    }
}
