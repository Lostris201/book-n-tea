<?php

namespace App\Filament\Resources\CafeTables;

use App\Filament\Resources\CafeTables\Pages\CreateCafeTable;
use App\Filament\Resources\CafeTables\Pages\EditCafeTable;
use App\Filament\Resources\CafeTables\Pages\ListCafeTables;
use App\Filament\Resources\CafeTables\Pages\PrintTableQrCodes;
use App\Models\CafeTable;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use UnitEnum;

class CafeTableResource extends Resource
{
    protected static ?string $model = CafeTable::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedQrCode;

    protected static string|UnitEnum|null $navigationGroup = 'Operasyon';

    protected static ?int $navigationSort = 3;

    protected static ?string $modelLabel = 'masa';

    protected static ?string $pluralModelLabel = 'Masalar';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()->columns(3)->schema([
                TextInput::make('number')
                    ->label('Masa no')
                    ->required()
                    ->integer()
                    ->minValue(1)
                    ->maxValue(9999)
                    ->unique(ignoreRecord: true),
                TextInput::make('name')->label('Ad')->required()->maxLength(255)->placeholder('Masa 01'),
                Toggle::make('is_active')
                    ->label('Aktif')
                    ->helperText('Pasif masalardan sipariş ve çağrı alınmaz.')
                    ->default(true)
                    ->inline(false),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('number')
            ->columns([
                TextColumn::make('number')->label('No')->sortable(),
                TextColumn::make('name')->label('Ad')->searchable(),
                TextColumn::make('open_orders_count')
                    ->label('Açık sipariş')
                    ->counts(['orders as open_orders_count' => fn ($query) => $query->open()]),
                ToggleColumn::make('is_active')->label('Aktif'),
            ])
            ->recordActions([
                self::regenerateTokenAction(),
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    public static function regenerateTokenAction(): Action
    {
        return Action::make('regenerateToken')
            ->label('QR yenile')
            ->icon(Heroicon::OutlinedArrowPath)
            ->color('warning')
            ->requiresConfirmation()
            ->modalHeading('QR kodu yenilensin mi?')
            ->modalDescription('Masadaki mevcut QR kod çalışmayı durdurur. Yeni kodu yazdırıp masaya koymanız gerekir.')
            ->authorize('update')
            ->action(function (CafeTable $record): void {
                $record->regenerateToken();

                Notification::make()->title("{$record->name} için yeni QR kod oluşturuldu.")->success()->send();
            });
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCafeTables::route('/'),
            'create' => CreateCafeTable::route('/create'),
            'qr' => PrintTableQrCodes::route('/qr'),
            'edit' => EditCafeTable::route('/{record}/edit'),
        ];
    }
}
