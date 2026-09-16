<?php

use App\Http\Middleware\EnsureUserHasRole;
use App\Http\Middleware\ForceJsonResponse;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->api(prepend: [ForceJsonResponse::class]);

        $middleware->alias([
            'role' => EnsureUserHasRole::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // API errors are always { "error": "Türkçe mesaj" } — the UI shows them directly.
        $exceptions->render(function (Throwable $e, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            [$status, $message] = match (true) {
                $e instanceof AuthenticationException => [401, 'Bu işlem için giriş yapmanız gerekiyor.'],
                $e instanceof AuthorizationException,
                $e instanceof AccessDeniedHttpException => [403, 'Bu işlem için yetkiniz yok.'],
                $e instanceof ValidationException => [422, collect($e->errors())->flatten()->first() ?? 'Geçersiz istek.'],
                $e instanceof ModelNotFoundException,
                $e instanceof NotFoundHttpException => [404, 'Kayıt bulunamadı.'],
                $e instanceof MethodNotAllowedHttpException => [405, 'Bu işlem desteklenmiyor.'],
                $e instanceof ThrottleRequestsException => [429, 'Çok fazla istek gönderildi. Lütfen biraz sonra tekrar deneyin.'],
                $e instanceof HttpExceptionInterface => [$e->getStatusCode(), 'İstek işlenemedi.'],
                default => [500, null],
            };

            // Unexpected errors: show the debug page locally, a generic message in production.
            if ($status === 500) {
                return config('app.debug') ? null : response()->json(['error' => 'Sunucu hatası.'], 500);
            }

            $headers = $e instanceof HttpExceptionInterface ? $e->getHeaders() : [];

            return response()->json(['error' => $message], $status, $headers);
        });
    })->create();
