<?php

namespace Tests\Feature\Integrations;

use App\Integrations\Http\IntegrationHttp;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class IntegrationHttpTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.reservation.base_url' => 'https://api.provider.test/v1',
            'services.reservation.timeout' => 10,
        ]);
    }

    public function test_calls_are_logged_without_credentials(): void
    {
        Http::fake(['api.provider.test/*' => Http::response(['ok' => true], 200)]);
        $log = $this->captureLog('integrations');

        $response = IntegrationHttp::make('fake')
            ->withToken('super-secret-bearer')
            ->withHeaders(['X-Api-Key' => 'k_live_abc'])
            ->get('/bookings', ['api_key' => 'query-secret', 'from' => '2026-09-01']);

        $this->assertTrue($response->ok());
        Http::assertSent(fn ($request) => $request->url() === 'https://api.provider.test/v1/bookings?api_key=query-secret&from=2026-09-01');

        $records = json_encode($log->getRecords());
        $this->assertTrue($log->hasInfoThatContains('Integration HTTP call'));
        $this->assertStringContainsString('from=2026-09-01', $records);
        $this->assertStringNotContainsString('super-secret-bearer', $records);
        $this->assertStringNotContainsString('k_live_abc', $records);
        $this->assertStringNotContainsString('query-secret', $records);
    }

    public function test_auth_failures_go_to_security_log(): void
    {
        Http::fake(['api.provider.test/*' => Http::response(['error' => 'unauthorized'], 401)]);
        $security = $this->captureLog('security');

        $response = IntegrationHttp::make('fake')->withToken('bad-token')->get('/ping');

        $this->assertSame(401, $response->status());
        $this->assertTrue($security->hasWarningThatContains('Integration authentication failed'));
        $this->assertStringNotContainsString('bad-token', json_encode($security->getRecords()));
    }

    public function test_server_errors_are_retried(): void
    {
        Http::fakeSequence('api.provider.test/*')
            ->push(['error' => 'down'], 503)
            ->push(['ok' => true], 200);

        $response = IntegrationHttp::make('fake')->get('/ping');

        $this->assertTrue($response->ok());
        Http::assertSentCount(2);
    }
}
