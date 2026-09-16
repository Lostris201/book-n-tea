<?php

namespace App\Filament\Resources\Orders;

use App\Enums\OrderStatus;
use App\Filament\Resources\Orders\Pages\ListOrders;
use App\Filament\Resources\Orders\Pages\ViewOrder;
use App\Filament\Support\Price;
use App\Models\Order;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedReceiptPercent;

    protected static string|UnitEnum|null $navigationGroup = 'Operasyon';

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'sipariş';

    protected static ?string $pluralModelLabel = 'Siparişler';

    protected static ?string $recordTitleAttribute = 'public_id';

    public const STATUS_LABELS = [
        'new' => 'Yeni',
        'preparing' => 'Hazırlanıyor',
        'ready' => 'Hazır',
        'delivered' => 'Teslim edildi',
        'done' => 'Kapandı',
    ];

    public const STATUS_COLORS = [
        'new' => 'danger',
        'preparing' => 'warning',
        'ready' => 'info',
        'delivered' => 'success',
        'done' => 'gray',
    ];

    public static function getNavigationBadge(): ?string
    {
        $open = Order::open()->count();

        return $open > 0 ? (string) $open : null;
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Sipariş')->columns(4)->columnSpanFull()->schema([
                TextEntry::make('table.name')->label('Masa'),
                TextEntry::make('status')
                    ->label('Durum')
                    ->badge()
                    ->formatStateUsing(fn (OrderStatus $state) => self::STATUS_LABELS[$state->value])
                    ->color(fn (OrderStatus $state) => self::STATUS_COLORS[$state->value]),
                TextEntry::make('total_cents')->label('Toplam')->formatStateUsing(fn ($state) => Price::format((int) $state)),
                TextEntry::make('public_id')->label('Sipariş no')->copyable(),
                TextEntry::make('created_at')->label('Oluşturuldu')->dateTime('d.m.Y H:i'),
                TextEntry::make('updated_at')->label('Güncellendi')->dateTime('d.m.Y H:i'),
                TextEntry::make('closed_at')->label('Kapandı')->dateTime('d.m.Y H:i')->placeholder('—'),
                IconEntry::make('has_new_items')->label('Sonradan eklenen var')->boolean(),
                TextEntry::make('note')->label('Not')->placeholder('—')->columnSpanFull(),
            ]),
            Section::make('Ürünler')->columnSpanFull()->schema([
                RepeatableEntry::make('items')
                    ->label('Ürünler')
                    ->hiddenLabel()
                    ->columns(5)
                    ->schema([
                        TextEntry::make('name_snapshot')->label('Ürün')->columnSpan(2)
                            ->badge(fn ($record) => $record->is_new)
                            ->color(fn ($record) => $record->is_new ? 'danger' : null),
                        TextEntry::make('options_snapshot')
                            ->label('Seçenekler')
                            ->getStateUsing(fn ($record) => collect($record->options_snapshot ?? [])->pluck('name')->implode(', ') ?: '—'),
                        TextEntry::make('qty')->label('Adet'),
                        TextEntry::make('line_total')
                            ->label('Tutar')
                            ->getStateUsing(fn ($record) => Price::format($record->lineTotalCents()).' ('.$record->qty.' × '.Price::format($record->unit_price_cents).')'),
                    ]),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->poll('10s')
            ->columns([
                TextColumn::make('created_at')->label('Zaman')->dateTime('d.m.Y H:i')->sortable(),
                TextColumn::make('table.name')->label('Masa')->sortable(),
                TextColumn::make('status')
                    ->label('Durum')
                    ->badge()
                    ->formatStateUsing(fn (OrderStatus $state) => self::STATUS_LABELS[$state->value])
                    ->color(fn (OrderStatus $state) => self::STATUS_COLORS[$state->value]),
                TextColumn::make('items_count')->label('Kalem')->counts('items'),
                Price::column('total_cents', 'Toplam')->sortable(),
                TextColumn::make('note')->label('Not')->limit(40)->toggleable(),
                TextColumn::make('public_id')->label('Sipariş no')->toggleable(isToggledHiddenByDefault: true)->searchable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Durum')
                    ->options(self::STATUS_LABELS),
                SelectFilter::make('cafe_table_id')
                    ->label('Masa')
                    ->relationship('table', 'name'),
                Filter::make('created_at')
                    ->label('Tarih')
                    ->schema([
                        DatePicker::make('from')->label('Başlangıç'),
                        DatePicker::make('until')->label('Bitiş'),
                    ])
                    ->query(fn (Builder $query, array $data) => $query
                        ->when($data['from'] ?? null, fn (Builder $q, $date) => $q->whereDate('created_at', '>=', $date))
                        ->when($data['until'] ?? null, fn (Builder $q, $date) => $q->whereDate('created_at', '<=', $date)))
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if ($data['from'] ?? null) {
                            $indicators[] = 'Başlangıç: '.\Illuminate\Support\Carbon::parse($data['from'])->format('d.m.Y');
                        }
                        if ($data['until'] ?? null) {
                            $indicators[] = 'Bitiş: '.\Illuminate\Support\Carbon::parse($data['until'])->format('d.m.Y');
                        }

                        return $indicators;
                    }),
            ])
            ->recordActions([
                ViewAction::make(),
                self::changeStatusAction(),
            ]);
    }

    public static function changeStatusAction(): Action
    {
        return Action::make('changeStatus')
            ->label('Durum')
            ->icon(Heroicon::OutlinedArrowsRightLeft)
            ->authorize('update')
            ->fillForm(fn (Order $record) => ['status' => $record->status->value])
            ->schema([
                Select::make('status')->label('Durum')->options(self::STATUS_LABELS)->required(),
            ])
            ->action(function (Order $record, array $data): void {
                $status = OrderStatus::from($data['status']);
                $record->status = $status;
                $record->closed_at = $status === OrderStatus::Done ? ($record->closed_at ?? now()) : null;
                $record->save();

                Notification::make()->title('Sipariş durumu güncellendi.')->success()->send();
            });
    }

    public static function getPages(): array
    {
        return [
            'index' => ListOrders::route('/'),
            'view' => ViewOrder::route('/{record}'),
        ];
    }
}
