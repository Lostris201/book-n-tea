<?php

namespace App\Filament\Resources\Orders\Pages;

use App\Filament\Resources\Orders\OrderResource;
use App\Filament\Widgets\RevenueChart;
use App\Models\Order;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListOrders extends ListRecords
{
    protected static string $resource = OrderResource::class;

    protected function getHeaderWidgets(): array
    {
        return [
            RevenueChart::class,
        ];
    }

    public function getTabs(): array
    {
        return [
            'open' => Tab::make('Açık')
                ->badge(fn () => Order::open()->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->open()),
            'today' => Tab::make('Bugün')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereDate('created_at', today())),
            'all' => Tab::make('Tümü'),
        ];
    }
}
