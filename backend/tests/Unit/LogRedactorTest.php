<?php

namespace Tests\Unit;

use App\Support\LogRedactor;
use PHPUnit\Framework\TestCase;

class LogRedactorTest extends TestCase
{
    public function test_masks_sensitive_keys_recursively(): void
    {
        $input = [
            'headers' => [
                'Authorization' => 'Bearer abc.def',
                'X-Api-Key' => 'k-123',
                'Accept' => 'application/json',
            ],
            'query' => ['api_key' => 'secret', 'from' => '2026-09-01'],
            'body' => [
                'reservation' => ['customer_phone' => '+90 532 000 00 00', 'party_size' => 4, 'name' => 'Ayşe'],
                'client_secret' => 'shh',
                'access_token' => 'tok',
                'password' => 'p',
            ],
        ];

        $out = LogRedactor::redact($input);

        $this->assertSame(LogRedactor::MASK, $out['headers']['Authorization']);
        $this->assertSame(LogRedactor::MASK, $out['headers']['X-Api-Key']);
        $this->assertSame('application/json', $out['headers']['Accept']);
        $this->assertSame(LogRedactor::MASK, $out['query']['api_key']);
        $this->assertSame('2026-09-01', $out['query']['from']);
        $this->assertSame(LogRedactor::MASK, $out['body']['reservation']['customer_phone']);
        $this->assertSame(4, $out['body']['reservation']['party_size']);
        $this->assertSame('Ayşe', $out['body']['reservation']['name']);
        $this->assertSame(LogRedactor::MASK, $out['body']['client_secret']);
        $this->assertSame(LogRedactor::MASK, $out['body']['access_token']);
        $this->assertSame(LogRedactor::MASK, $out['body']['password']);
    }

    public function test_masks_secrets_inside_strings(): void
    {
        $this->assertSame(
            'https://api.example.test/v1/bookings?from=2026&api_key=[REDACTED]&token=[REDACTED]',
            LogRedactor::redactString('https://api.example.test/v1/bookings?from=2026&api_key=abc123&token=xyz'),
        );
        $this->assertSame('401: Bearer [REDACTED] rejected', LogRedactor::redactString('401: Bearer eyJhbGciOi.x.y rejected'));
        $this->assertSame('no secrets here', LogRedactor::redactString('no secrets here'));
    }
}
