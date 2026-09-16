<?php

namespace App\Services\Tables;

use App\Exceptions\ApiException;
use App\Models\CafeTable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TableResolver
{
    /**
     * Resolves the QR token to an active table, or throws 404 and records it in the security log.
     */
    public function resolve(mixed $token, Request $request): CafeTable
    {
        $table = is_string($token) && strlen($token) === 32
            ? CafeTable::active()->where('qr_token', $token)->first()
            : null;

        if (! $table) {
            Log::channel('security')->warning('Invalid table token', [
                'ip' => $request->ip(),
                'path' => $request->path(),
                // Never log the token itself; a short hash is enough to spot repeats.
                'token_hash' => is_string($token) ? substr(hash('sha256', $token), 0, 12) : null,
            ]);

            throw new ApiException('Masa bulunamadı. Lütfen masanızdaki QR kodu tekrar okutun.', 404);
        }

        return $table;
    }
}
