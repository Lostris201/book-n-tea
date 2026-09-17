<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Integrations\Reservations\InvalidProviderPayload;
use App\Integrations\Reservations\ReservationProviderManager;
use App\Jobs\ProcessReservationWebhook;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * POST /api/webhooks/reservations/{provider}
 * Verify → parse → queue → 202. Heavy work happens in ProcessReservationWebhook.
 */
class ReservationWebhookController extends Controller
{
    public function __invoke(Request $request, string $provider, ReservationProviderManager $manager): JsonResponse
    {
        $adapter = $manager->forWebhook($provider);

        if (! $adapter || ! $adapter->supportsWebhooks()) {
            Log::channel('security')->warning('Webhook for unknown or disabled provider', [
                'provider' => substr($provider, 0, 50),
                'ip' => $request->ip(),
            ]);

            return response()->json(['error' => 'Entegrasyon bulunamadı.'], 404);
        }

        if (! $adapter->verifyWebhook($request)) {
            Log::channel('security')->warning('Webhook signature verification failed', [
                'provider' => $adapter->name(),
                'ip' => $request->ip(),
            ]);

            return response()->json(['error' => 'Geçersiz imza.'], 401);
        }

        try {
            $data = $adapter->parseWebhook($request);
        } catch (InvalidProviderPayload $e) {
            Log::channel('integrations')->warning('Invalid webhook payload', [
                'provider' => $adapter->name(),
                'reason' => $e->getMessage(),
            ]);

            return response()->json(['error' => 'Geçersiz veri.'], 422);
        }

        ProcessReservationWebhook::dispatch($adapter->name(), $data->toArray());

        return response()->json(['received' => true], 202);
    }
}
