<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CafeTable;
use App\Services\Tables\TableResolver;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TableController extends Controller
{
    public function __construct(private readonly TableResolver $tables) {}

    /** GET /api/tables/resolve?t=... — public, used by the customer menu. */
    public function resolve(Request $request): JsonResponse
    {
        $table = $this->tables->resolve($request->query('t'), $request);

        return response()->json(['number' => $table->number, 'name' => $table->name]);
    }

    /** GET /api/tables — admin/manager only, includes QR tokens for printing. */
    public function index(): JsonResponse
    {
        return response()->json(
            CafeTable::orderBy('number')->get()->map(fn (CafeTable $t) => [
                'number' => $t->number,
                'name' => $t->name,
                'isActive' => $t->is_active,
                'qrToken' => $t->qr_token,
            ])->values()
        );
    }
}
