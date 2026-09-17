<?php

namespace App\Integrations\Http;

use App\Support\LogRedactor;
use Closure;
use GuzzleHttp\Promise\PromiseInterface;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Throwable;

/**
 * The only way adapters should call a provider: base URL from config, 10s timeout, retries on connection
 * errors / 5xx, and redacted logging (no bodies, no credentials). 401/403 also go to the security log.
 */
final class IntegrationHttp
{
    public static function make(string $provider): PendingRequest
    {
        $config = config('services.reservation');

        return Http::baseUrl((string) ($config['base_url'] ?? ''))
            ->timeout((int) ($config['timeout'] ?? 10))
            ->connectTimeout(5)
            ->acceptJson()
            ->retry(
                times: 3,
                sleepMilliseconds: fn (int $attempt) => 200 * $attempt,
                when: fn (Throwable $e) => $e instanceof ConnectionException
                    || ($e instanceof RequestException && $e->response->serverError()),
                throw: false,
            )
            ->withMiddleware(self::loggingMiddleware($provider));
    }

    private static function loggingMiddleware(string $provider): Closure
    {
        return fn (callable $handler) => function (RequestInterface $request, array $options) use ($handler, $provider): PromiseInterface {
            $started = microtime(true);

            return $handler($request, $options)->then(
                function (ResponseInterface $response) use ($request, $provider, $started) {
                    $context = self::context($provider, $request, $started) + ['status' => $response->getStatusCode()];

                    Log::channel('integrations')->info('Integration HTTP call', $context);

                    if (in_array($response->getStatusCode(), [401, 403], true)) {
                        Log::channel('security')->warning('Integration authentication failed', $context);
                    }

                    return $response;
                },
                function ($reason) use ($request, $provider, $started) {
                    Log::channel('integrations')->warning('Integration HTTP call failed', self::context($provider, $request, $started) + [
                        'error' => LogRedactor::redactString($reason instanceof Throwable ? $reason->getMessage() : (string) $reason),
                    ]);

                    return \GuzzleHttp\Promise\Create::rejectionFor($reason);
                },
            );
        };
    }

    private static function context(string $provider, RequestInterface $request, float $started): array
    {
        return [
            'provider' => $provider,
            'method' => $request->getMethod(),
            'url' => LogRedactor::redactString((string) $request->getUri()),
            'headers' => LogRedactor::redact(array_map(fn (array $v) => implode(', ', $v), $request->getHeaders())),
            'duration_ms' => (int) round((microtime(true) - $started) * 1000),
        ];
    }
}
