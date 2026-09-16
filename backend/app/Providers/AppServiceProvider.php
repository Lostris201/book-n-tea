<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureRateLimiting();
    }

    private function configureRateLimiting(): void
    {
        $tooMany = function (string $limiter) {
            return function (Request $request, array $headers) use ($limiter) {
                Log::channel('security')->warning('Rate limit exceeded', [
                    'limiter' => $limiter,
                    'ip' => $request->ip(),
                    'path' => $request->path(),
                ]);

                return response()->json(
                    ['error' => 'Çok fazla istek gönderildi. Lütfen biraz sonra tekrar deneyin.'],
                    429,
                    $headers,
                );
            };
        };

        // Public order endpoint: per IP and per table.
        RateLimiter::for('orders', fn (Request $request) => [
            Limit::perMinute(10)->by('ip:'.$request->ip())->response($tooMany('orders')),
            Limit::perMinute(10)->by('table:'.self::tokenKey($request))->response($tooMany('orders')),
        ]);

        RateLimiter::for('waiter-calls', fn (Request $request) => [
            Limit::perMinute(10)->by('ip:'.$request->ip())->response($tooMany('waiter-calls')),
            Limit::perMinute(10)->by('table:'.self::tokenKey($request))->response($tooMany('waiter-calls')),
        ]);

        RateLimiter::for('table-resolve', fn (Request $request) => Limit::perMinute(30)
            ->by('ip:'.$request->ip())
            ->response($tooMany('table-resolve')));

        RateLimiter::for('login', fn (Request $request) => Limit::perMinute(5)
            ->by('ip:'.$request->ip())
            ->response($tooMany('login')));
    }

    private static function tokenKey(Request $request): string
    {
        $token = $request->input('table_token');

        return is_string($token) ? hash('sha256', $token) : 'none';
    }
}
