<?php

namespace App\Filament\Widgets;

use App\Enums\OrderStatus;
use App\Models\Order;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;

class RevenueChart extends ChartWidget
{
    protected static ?int $sort = 2;

    protected ?string $heading = 'Günlük ciro (son 14 gün)';

    protected ?string $maxHeight = '260px';

    protected int|string|array $columnSpan = 'full';

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getData(): array
    {
        $from = today()->subDays(13);

        $totals = Order::query()
            ->where('status', OrderStatus::Done)
            ->where('closed_at', '>=', $from)
            ->get(['closed_at', 'total_cents'])
            ->groupBy(fn (Order $order) => $order->closed_at->toDateString())
            ->map(fn ($orders) => $orders->sum('total_cents'));

        $labels = [];
        $values = [];
        for ($day = $from->copy(); $day->lte(today()); $day->addDay()) {
            $labels[] = $day->format('d.m');
            $values[] = round(($totals[$day->toDateString()] ?? 0) / 100, 2);
        }

        return [
            'datasets' => [
                ['label' => 'Ciro (₺)', 'data' => $values],
            ],
            'labels' => $labels,
        ];
    }
}
