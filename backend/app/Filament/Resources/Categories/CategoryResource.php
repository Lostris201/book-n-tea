<?php

namespace App\Filament\Resources\Categories;

use App\Filament\Resources\Categories\Pages\CreateCategory;
use App\Filament\Resources\Categories\Pages\EditCategory;
use App\Filament\Resources\Categories\Pages\ListCategories;
use App\Models\Category;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use UnitEnum;

class CategoryResource extends Resource
{
    protected static ?string $model = Category::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static string|UnitEnum|null $navigationGroup = 'Menü';

    protected static ?int $navigationSort = 2;

    protected static ?string $modelLabel = 'kategori';

    protected static ?string $pluralModelLabel = 'Kategoriler';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()->columns(2)->schema([
                TextInput::make('name')->label('Ad')->required()->maxLength(255),
                TextInput::make('icon')->label('İkon (emoji)')->maxLength(16)->placeholder('🍵'),
                TextInput::make('slug')
                    ->label('Kısa kod')
                    ->helperText('Menü bağlantılarında kullanılır. Boş bırakılırsa addan üretilir; sonradan değiştirilemez.')
                    ->maxLength(255)
                    ->alphaDash()
                    ->unique(ignoreRecord: true)
                    ->disabledOn('edit'),
                Toggle::make('is_active')->label('Menüde göster')->default(true),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->reorderable('sort_order')
            ->defaultSort('sort_order')
            ->columns([
                TextColumn::make('icon')->label(''),
                TextColumn::make('name')->label('Ad')->searchable(),
                TextColumn::make('slug')->label('Kısa kod')->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('products_count')->label('Ürün')->counts('products'),
                ToggleColumn::make('is_active')->label('Aktif'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCategories::route('/'),
            'create' => CreateCategory::route('/create'),
            'edit' => EditCategory::route('/{record}/edit'),
        ];
    }
}
