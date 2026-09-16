<?php

namespace App\Filament\Resources\Reservations;

use App\Enums\ReservationSource;
use App\Enums\ReservationStatus;
use App\Filament\Resources\Reservations\Pages\CreateReservation;
use App\Filament\Resources\Reservations\Pages\EditReservation;
use App\Filament\Resources\Reservations\Pages\ListReservations;
use App\Models\Reservation;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class ReservationResource extends Resource
{
    protected static ?string $model = Reservation::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    protected static string|UnitEnum|null $navigationGroup = 'Operasyon';

    protected static ?int $navigationSort = 2;

    protected static ?string $modelLabel = 'rezervasyon';

    protected static ?string $pluralModelLabel = 'Rezervasyonlar';

    protected static ?string $recordTitleAttribute = 'customer_name';

    public const STATUS_LABELS = [
        'pending' => 'Bekliyor',
        'confirmed' => 'Onaylandı',
        'seated' => 'Oturdu',
        'cancelled' => 'İptal',
        'no_show' => 'Gelmedi',
    ];

    public const STATUS_COLORS = [
        'pending' => 'warning',
        'confirmed' => 'info',
        'seated' => 'success',
        'cancelled' => 'gray',
        'no_show' => 'danger',
    ];

    public static function form(Schema $schema): Schema
    {
        // External reservations are owned by the provider: only table, status and note are editable here.
        $isExternal = fn (?Reservation $record) => $record?->isExternal() ?? false;

        return $schema->components([
            Section::make()->columns(2)->columnSpanFull()->schema([
                TextInput::make('customer_name')->label('Ad soyad')->required()->maxLength(255)->disabled($isExternal),
                TextInput::make('customer_phone')->label('Telefon')->tel()->maxLength(32)->disabled($isExternal),
                DateTimePicker::make('reserved_for')
                    ->label('Tarih / saat')
                    ->required()
                    ->seconds(false)
                    ->minutesStep(15)
                    ->disabled($isExternal),
                TextInput::make('party_size')->label('Kişi sayısı')->required()->integer()->minValue(1)->maxValue(100)->disabled($isExternal),
                Select::make('cafe_table_id')->label('Masa')->relationship('table', 'name')->placeholder('Atanmadı'),
                Select::make('status')
                    ->label('Durum')
                    ->options(self::STATUS_LABELS)
                    ->default(ReservationStatus::Pending->value)
                    ->required(),
                Textarea::make('note')->label('Not')->rows(3)->maxLength(1000)->columnSpanFull(),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('reserved_for')
            ->defaultGroup(Group::make('reserved_for')->label('Gün')->date())
            ->columns([
                TextColumn::make('reserved_for')->label('Saat')->dateTime('H:i')->sortable(),
                TextColumn::make('customer_name')->label('Ad soyad')->searchable(),
                TextColumn::make('party_size')->label('Kişi'),
                TextColumn::make('table.name')->label('Masa')->placeholder('—'),
                TextColumn::make('status')
                    ->label('Durum')
                    ->badge()
                    ->formatStateUsing(fn (ReservationStatus $state) => self::STATUS_LABELS[$state->value])
                    ->color(fn (ReservationStatus $state) => self::STATUS_COLORS[$state->value]),
                TextColumn::make('source')
                    ->label('Kaynak')
                    ->badge()
                    ->formatStateUsing(fn (ReservationSource $state, Reservation $record) => $state === ReservationSource::Native
                        ? 'Panel'
                        : ($record->external_provider ?: 'Harici'))
                    ->color(fn (ReservationSource $state) => $state === ReservationSource::Native ? 'primary' : 'gray'),
                TextColumn::make('customer_phone')->label('Telefon')->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Filter::make('upcoming')
                    ->label('Bugün ve sonrası')
                    ->default()
                    ->query(fn (Builder $query) => $query->where('reserved_for', '>=', today())),
                Filter::make('day')
                    ->label('Gün')
                    ->schema([DatePicker::make('day')->label('Gün')])
                    ->query(fn (Builder $query, array $data) => $query->when(
                        $data['day'] ?? null,
                        fn (Builder $q, $day) => $q->whereDate('reserved_for', $day),
                    )),
                SelectFilter::make('status')->label('Durum')->options(self::STATUS_LABELS),
                SelectFilter::make('source')->label('Kaynak')->options(['native' => 'Panel', 'external' => 'Harici']),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    public static function getNavigationBadge(): ?string
    {
        $today = Reservation::forDay(today())->whereNotIn('status', ['cancelled', 'no_show'])->count();

        return $today > 0 ? (string) $today : null;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListReservations::route('/'),
            'create' => CreateReservation::route('/create'),
            'edit' => EditReservation::route('/{record}/edit'),
        ];
    }
}
