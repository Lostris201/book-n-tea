<?php

namespace App\Http\Controllers\Api;

use App\Enums\WaiterCallType;
use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Models\WaiterCall;
use App\Services\Orders\OrderPresenter;
use App\Services\Tables\TableResolver;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class WaiterCallController extends Controller
{
    public function __construct(private readonly TableResolver $tables) {}

    /** GET /api/waiter-calls — staff: pending calls, oldest first. */
    public function index(): JsonResponse
    {
        return response()->json(
            WaiterCall::pending()->with('table')->orderBy('created_at')->orderBy('id')->get()
                ->map(fn (WaiterCall $call) => $this->present($call))->values()
        );
    }

    /** POST /api/waiter-calls { table_token, type: waiter|bill, reason? } — public. */
    public function store(Request $request): JsonResponse
    {
        $token = $request->input('table_token');
        $type = WaiterCallType::tryFrom((string) $request->input('type'));

        if (! filled($token) || ! $type) {
            return response()->json(['error' => 'Masa ve çağrı türü gerekli.'], 400);
        }

        $table = $this->tables->resolve($token, $request);

        $settingKey = $type === WaiterCallType::Bill ? 'request_bill_enabled' : 'call_waiter_enabled';
        if (Setting::getValue($settingKey, true) === false) {
            return response()->json(['error' => 'Bu özellik şu anda kullanılamıyor.'], 403);
        }

        $reason = $request->input('reason');
        $reason = is_scalar($reason) && trim((string) $reason) !== '' ? Str::limit(trim((string) $reason), 100, '') : null;

        // Repeated taps while a call is still pending don't create duplicates.
        $existing = WaiterCall::pending()
            ->where('cafe_table_id', $table->id)
            ->where('type', $type)
            ->when($reason === null, fn ($q) => $q->whereNull('reason'), fn ($q) => $q->where('reason', $reason))
            ->first();

        if ($existing) {
            return response()->json($this->present($existing->load('table')));
        }

        $call = WaiterCall::create(['cafe_table_id' => $table->id, 'type' => $type, 'reason' => $reason]);

        return response()->json($this->present($call->load('table')), 201);
    }

    /** PATCH /api/waiter-calls/{id} — staff: mark resolved. */
    public function resolve(int $id): JsonResponse
    {
        $call = WaiterCall::with('table')->find($id);

        if (! $call) {
            return response()->json(['error' => 'Çağrı bulunamadı.'], 404);
        }

        $call->resolved_at ??= now();
        $call->save();

        return response()->json($this->present($call));
    }

    /** @return array<string, mixed> */
    private function present(WaiterCall $call): array
    {
        return [
            'id' => $call->id,
            'table' => sprintf('%02d', $call->table->number),
            'tableName' => $call->table->name,
            'type' => $call->type->value,
            'reason' => $call->reason,
            'resolved' => $call->resolved_at !== null,
            'createdAt' => OrderPresenter::iso($call->created_at),
        ];
    }
}
