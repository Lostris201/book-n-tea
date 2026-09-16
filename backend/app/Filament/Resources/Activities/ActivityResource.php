<?php

namespace App\Filament\Resources\Activities;

use App\Filament\Resources\Activities\Pages\ListActivities;
use App\Filament\Resources\Activities\Pages\ViewActivity;
use BackedEnum;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Spatie\Activitylog\Models\Activity;
use UnitEnum;

class ActivityResource extends Resource
{
    protected static ?string $model = Activity::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static string|UnitEnum|null $navigationGroup = 'Yönetim';

    protected static ?int $navigationSort = 3;

    protected static ?string $modelLabel = 'kayıt';

    protected static ?string $pluralModelLabel = 'İşlem kayıtları';

    public const SUBJECT_LABELS = [
        'App\Models\Product' => 'Ürün',
        'App\Models\Category' => 'Kategori',
        'App\Models\OptionGroup' => 'Seçenek grubu',
        'App\Models\Option' => 'Seçenek',
        'App\Models\CafeTable' => 'Masa',
        'App\Models\Order' => 'Sipariş',
        'App\Models\Reservation' => 'Rezervasyon',
        'App\Models\Setting' => 'Ayar',
        'App\Models\User' => 'Kullanıcı',
        'App\Models\Integration' => 'Entegrasyon',
    ];

    public const EVENT_LABELS = [
        'created' => 'Oluşturuldu',
        'updated' => 'Güncellendi',
        'deleted' => 'Silindi',
    ];

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()->columns(3)->columnSpanFull()->schema([
                TextEntry::make('created_at')->label('Zaman')->dateTime('d.m.Y H:i:s'),
                TextEntry::make('causer.name')->label('Yapan')->placeholder('Sistem / müşteri'),
                TextEntry::make('event')->label('İşlem')->formatStateUsing(fn (?string $state) => self::EVENT_LABELS[$state] ?? $state),
                TextEntry::make('subject_type')->label('Kayıt türü')->formatStateUsing(fn (?string $state) => self::SUBJECT_LABELS[$state] ?? $state),
                TextEntry::make('subject_id')->label('Kayıt no'),
                TextEntry::make('description')->label('Açıklama'),
                TextEntry::make('properties')
                    ->label('Değişiklikler')
                    ->getStateUsing(fn (Activity $record) => json_encode($record->properties, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES))
                    ->fontFamily('mono')
                    ->extraAttributes(['style' => 'white-space: pre-wrap'])
                    ->columnSpanFull(),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('created_at')->label('Zaman')->dateTime('d.m.Y H:i')->sortable(),
                TextColumn::make('causer.name')->label('Yapan')->placeholder('Sistem / müşteri'),
                TextColumn::make('event')->label('İşlem')->badge()->formatStateUsing(fn (?string $state) => self::EVENT_LABELS[$state] ?? $state),
                TextColumn::make('subject_type')->label('Kayıt türü')->formatStateUsing(fn (?string $state) => self::SUBJECT_LABELS[$state] ?? $state),
                TextColumn::make('subject_id')->label('No'),
                TextColumn::make('description')->label('Açıklama')->limit(50),
            ])
            ->filters([
                SelectFilter::make('subject_type')->label('Kayıt türü')->options(self::SUBJECT_LABELS),
                SelectFilter::make('event')->label('İşlem')->options(self::EVENT_LABELS),
                SelectFilter::make('causer_id')
                    ->label('Yapan')
                    ->options(fn () => \App\Models\User::orderBy('name')->pluck('name', 'id')->all()),
            ])
            ->recordActions([
                ViewAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListActivities::route('/'),
            'view' => ViewActivity::route('/{record}'),
        ];
    }
}
