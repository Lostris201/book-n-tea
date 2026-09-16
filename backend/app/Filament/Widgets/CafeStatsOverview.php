<?php

namespace App\Filament\Widgets;

use App\Enums\OrderStatus;
use App\Enums\ReservationStatus;
use App\Filament\Support\Price;
use App\Models\Order;
use App\Models\Reservation;
use App\Models\WaiterCall;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class CafeStatsOverview extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected ?string $pollingInterval = '15s';

    protected int|array|null $columns = ['md' => 2, 'xl' => 5];

    protected function getStats(): array
    {
        $today = today();

        $openOrders = Order::open()->count();
        $todayOrders = Order::whereDate('created_at', $today)->count();
        // Revenue = orders closed (paid) today.
        $todayRevenue = (int) Order::where('status', OrderStatus::Done)->whereDate('closed_at', $today)->sum('total_cents');
        $todayReservations = Reservation::forDay($today)
            ->whereNotIn('status', [ReservationStatus::Cancelled, ReservationStatus::NoShow])
            ->count();
        $pendingCalls = WaiterCall::pending()->count();

        return [
            Stat::make('Açık siparişler', $openOrders)->color($openOrders > 0 ? 'warning' : 'gray'),
            Stat::make('Bugünkü siparişler', $todayOrders),
            Stat::make('Bugünkü ciro', Price::format($todayRevenue))->description('Kapatılan siparişler'),
            Stat::make('Bugünkü rezervasyonlar', $todayReservations),
            Stat::make('Bekleyen çağrılar', $pendingCalls)->color($pendingCalls > 0 ? 'danger' : 'gray'),
        ];
    }
}
