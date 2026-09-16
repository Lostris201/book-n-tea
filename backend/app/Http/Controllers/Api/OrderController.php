<?php

namespace App\Http\Controllers\Api;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\Legacy\LegacyOrderPresenter;
use App\Services\Legacy\LegacyOrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function __construct(private readonly LegacyOrderService $orders) {}

    public function index(Request $request): JsonResponse
    {
        $query = Order::with(['table', 'items'])->orderBy('created_at')->orderBy('id');

        $status = $request->query('status');
        if (is_string($status) && $status !== '') {
            $query->where('status', $status);
        } else {
            $query->open();
        }

        return response()->json(
            $query->get()->map(fn (Order $order) => LegacyOrderPresenter::present($order))->values()
        );
    }

    public function store(Request $request): JsonResponse
    {
        $table = $request->input('table');
        $items = $request->input('items');

        if (! filled($table) || ! is_array($items) || $items === []) {
            return response()->json(['error' => 'Masa ve ürünler gerekli.'], 400);
        }

        $cleanItems = $this->orders->cleanItems($items);
        if ($cleanItems === []) {
            return response()->json(['error' => 'Geçerli ürün yok.'], 400);
        }

        $cafeTable = $this->orders->findTable($table);
        if (! $cafeTable) {
            return response()->json(['error' => 'Masa bulunamadı.'], 404);
        }

        [$order, $created] = $this->orders->place(
            $cafeTable,
            $cleanItems,
            $this->orders->cleanNote($request->input('note')),
        );

        return response()->json(LegacyOrderPresenter::present($order), $created ? 201 : 200);
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

        return response()->json(LegacyOrderPresenter::present($order));
    }
}
