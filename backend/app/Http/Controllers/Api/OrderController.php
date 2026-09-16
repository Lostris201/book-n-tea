<?php

namespace App\Http\Controllers\Api;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\Orders\OrderPresenter;
use App\Services\Orders\OrderPricer;
use App\Services\Orders\OrderService;
use App\Services\Tables\TableResolver;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function __construct(
        private readonly OrderService $orders,
        private readonly OrderPricer $pricer,
        private readonly TableResolver $tables,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $query = Order::with(['table', 'items.product'])->orderBy('created_at')->orderBy('id');

        $status = $request->query('status');
        if (is_string($status) && $status !== '') {
            $query->where('status', $status);
        } else {
            $query->open();
        }

        return response()->json(
            $query->get()->map(fn (Order $order) => OrderPresenter::present($order))->values()
        );
    }

    /**
     * Body: { table_token, items: [{ product_id, qty, option_ids: [] }], note }
     * Prices always come from the database; any client-sent price/name is ignored.
     */
    public function store(Request $request): JsonResponse
    {
        $token = $request->input('table_token');
        $items = $request->input('items');

        if (! filled($token) || ! is_array($items) || $items === [] || ! array_is_list($items)) {
            return response()->json(['error' => 'Masa ve ürünler gerekli.'], 400);
        }

        $table = $this->tables->resolve($token, $request);
        $lines = $this->pricer->price($items);

        [$order, $created] = $this->orders->place($table, $lines, $this->orders->cleanNote($request->input('note')));

        return response()->json(OrderPresenter::present($order), $created ? 201 : 200);
    }

    public function update(Request $request, string $publicId): JsonResponse
    {
        $status = OrderStatus::tryFrom((string) $request->input('status'));

        if (! $status) {
            return response()->json(['error' => 'Geçersiz durum.'], 400);
        }

        $order = Order::where('public_id', $publicId)->first();

        if (! $order) {
            return response()->json(['error' => 'Sipariş bulunamadı.'], 404);
        }

        $order->status = $status;
        $order->closed_at = $status === OrderStatus::Done ? ($order->closed_at ?? now()) : null;
        $order->save();

        return response()->json(OrderPresenter::present($order));
    }
}
