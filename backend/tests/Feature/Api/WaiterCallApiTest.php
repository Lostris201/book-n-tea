<?php

namespace Tests\Feature\Api;

use App\Enums\UserRole;
use App\Models\CafeTable;
use App\Models\Setting;
use App\Models\User;
use App\Models\WaiterCall;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class WaiterCallApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    private function token(int $number = 4): string
    {
        return CafeTable::where('number', $number)->value('qr_token');
    }

    public function test_customer_can_call_waiter(): void
    {
        $this->postJson('/api/waiter-calls', ['table_token' => $this->token(), 'type' => 'waiter', 'reason' => 'Su & Peçete İsteği'])
            ->assertStatus(201)
            ->assertJsonPath('table', '04')
            ->assertJsonPath('type', 'waiter')
            ->assertJsonPath('reason', 'Su & Peçete İsteği')
            ->assertJsonPath('resolved', false);

        $this->assertSame(1, WaiterCall::count());
    }

    public function test_repeated_pending_call_is_not_duplicated(): void
    {
        $first = $this->postJson('/api/waiter-calls', ['table_token' => $this->token(), 'type' => 'bill'])->assertStatus(201);
        $this->postJson('/api/waiter-calls', ['table_token' => $this->token(), 'type' => 'bill'])
            ->assertStatus(200)
            ->assertJsonPath('id', $first->json('id'));

        // Different type or reason is a separate call.
        $this->postJson('/api/waiter-calls', ['table_token' => $this->token(), 'type' => 'waiter'])->assertStatus(201);
        $this->postJson('/api/waiter-calls', ['table_token' => $this->token(), 'type' => 'waiter', 'reason' => 'Masa Temizliği'])->assertStatus(201);
        $this->assertSame(3, WaiterCall::count());
    }

    public function test_validation(): void
    {
        $this->postJson('/api/waiter-calls', ['type' => 'waiter'])->assertStatus(400);
        $this->postJson('/api/waiter-calls', ['table_token' => $this->token(), 'type' => 'dance'])->assertStatus(400);
        $this->postJson('/api/waiter-calls', ['table_token' => str_repeat('x', 32), 'type' => 'waiter'])->assertStatus(404);
        $this->assertSame(0, WaiterCall::count());
    }

    public function test_respects_feature_toggles(): void
    {
        Setting::setValue('request_bill_enabled', false);

        $this->postJson('/api/waiter-calls', ['table_token' => $this->token(), 'type' => 'bill'])->assertStatus(403);
        $this->postJson('/api/waiter-calls', ['table_token' => $this->token(), 'type' => 'waiter'])->assertStatus(201);

        Setting::setValue('call_waiter_enabled', false);
        $this->postJson('/api/waiter-calls', ['table_token' => $this->token(5), 'type' => 'waiter'])->assertStatus(403);
    }

    public function test_is_rate_limited(): void
    {
        for ($i = 0; $i < 10; $i++) {
            $this->postJson('/api/waiter-calls', ['table_token' => $this->token(), 'type' => 'waiter']);
        }

        $this->postJson('/api/waiter-calls', ['table_token' => $this->token(), 'type' => 'waiter'])->assertStatus(429);
    }

    public function test_staff_lists_and_resolves_pending_calls(): void
    {
        $this->getJson('/api/waiter-calls')->assertStatus(401);

        $this->travelTo(now()->subMinute());
        $older = $this->postJson('/api/waiter-calls', ['table_token' => $this->token(8), 'type' => 'bill'])->json('id');
        $this->travelBack();
        $newer = $this->postJson('/api/waiter-calls', ['table_token' => $this->token(3), 'type' => 'waiter'])->json('id');

        Sanctum::actingAs(User::factory()->role(UserRole::Staff)->create());

        $this->assertSame([$older, $newer], array_column($this->getJson('/api/waiter-calls')->json(), 'id'));

        $this->patchJson("/api/waiter-calls/{$older}")->assertOk()->assertJsonPath('resolved', true);
        $this->assertSame([$newer], array_column($this->getJson('/api/waiter-calls')->json(), 'id'));

        $this->patchJson('/api/waiter-calls/999999')->assertStatus(404);
    }

    public function test_resolving_requires_staff(): void
    {
        $id = $this->postJson('/api/waiter-calls', ['table_token' => $this->token(), 'type' => 'waiter'])->json('id');

        $this->patchJson("/api/waiter-calls/{$id}")->assertStatus(401);
        $this->assertNull(WaiterCall::find($id)->resolved_at);
    }
}
