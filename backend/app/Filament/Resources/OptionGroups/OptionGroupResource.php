<?php

namespace App\Filament\Resources\OptionGroups;

use App\Filament\Resources\OptionGroups\Pages\CreateOptionGroup;
use App\Filament\Resources\OptionGroups\Pages\EditOptionGroup;
use App\Filament\Resources\OptionGroups\Pages\ListOptionGroups;
use App\Filament\Resources\OptionGroups\RelationManagers\OptionsRelationManager;
use App\Models\OptionGroup;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class OptionGroupResource extends Resource
{
    protected static ?string $model = OptionGroup::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedAdjustmentsHorizontal;

    protected static string|UnitEnum|null $navigationGroup = 'Menü';

    protected static ?int $navigationSort = 3;

    protected static ?string $modelLabel = 'seçenek grubu';

    protected static ?string $pluralModelLabel = 'Seçenek grupları';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()->columns(3)->schema([
                TextInput::make('name')->label('Ad')->required()->maxLength(255)->placeholder('Süt Tercihi'),
                TextInput::make('key')
                    ->label('Anahtar')
                    ->helperText('Örn. milk, sugar, extras. Sonradan değiştirilemez.')
                    ->required()
                    ->alphaDash()
                    ->maxLength(50)
                    ->unique(ignoreRecord: true)
                    ->disabledOn('edit'),
                Toggle::make('is_multi_select')
                    ->label('Birden fazla seçim')
                    ->helperText('Kapalıysa müşteri bu gruptan yalnızca bir seçenek seçebilir.')
                    ->inline(false),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('Ad')->searchable(),
                TextColumn::make('key')->label('Anahtar'),
                IconColumn::make('is_multi_select')->label('Çoklu seçim')->boolean(),
                TextColumn::make('options_count')->label('Seçenek')->counts('options'),
                TextColumn::make('products_count')->label('Ürün')->counts('products'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            OptionsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListOptionGroups::route('/'),
            'create' => CreateOptionGroup::route('/create'),
            'edit' => EditOptionGroup::route('/{record}/edit'),
        ];
    }
}
