<?php

namespace Tests\Feature\Api;

use App\Enums\UserRole;
use App\Models\CafeTable;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TableApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_resolves_valid_token(): void
    {
        $token = CafeTable::where('number', 7)->value('qr_token');

        $this->getJson('/api/tables/resolve?t='.$token)
            ->assertOk()
            ->assertExactJson(['number' => 7, 'name' => 'Masa 07']);
    }

    public function test_invalid_missing_or_inactive_token_returns_404(): void
    {
        $this->getJson('/api/tables/resolve?t=wrongwrongwrongwrongwrongwrong12')->assertStatus(404)->assertJsonStructure(['error']);
        $this->getJson('/api/tables/resolve')->assertStatus(404);
        $this->getJson('/api/tables/resolve?t[]=x')->assertStatus(404);

        $table = CafeTable::where('number', 2)->firstOrFail();
        $table->update(['is_active' => false]);
        $this->getJson('/api/tables/resolve?t='.$table->qr_token)->assertStatus(404);
    }

    public function test_resolve_is_rate_limited(): void
    {
        for ($i = 0; $i < 30; $i++) {
            $this->getJson('/api/tables/resolve?t=x');
        }

        $this->getJson('/api/tables/resolve?t=x')->assertStatus(429);
    }

    public function test_token_is_not_exposed_in_model_serialization(): void
    {
        $this->assertArrayNotHasKey('qr_token', CafeTable::first()->toArray());
    }

    public function test_table_list_with_tokens_is_admin_or_manager_only(): void
    {
        $this->getJson('/api/tables')->assertStatus(401);

        Sanctum::actingAs(User::factory()->role(UserRole::Staff)->create());
        $this->getJson('/api/tables')->assertStatus(403);

        Sanctum::actingAs(User::factory()->role(UserRole::Manager)->create());
        $response = $this->getJson('/api/tables')->assertOk()->assertJsonCount(12);
        $response->assertJsonPath('0.number', 1);
        $response->assertJsonPath('0.qrToken', CafeTable::where('number', 1)->value('qr_token'));
    }
}
