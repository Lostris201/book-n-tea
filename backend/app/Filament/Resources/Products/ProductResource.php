<?php

namespace App\Filament\Resources\Products;

use App\Filament\Resources\Products\Pages\CreateProduct;
use App\Filament\Resources\Products\Pages\EditProduct;
use App\Filament\Resources\Products\Pages\ListProducts;
use App\Filament\Support\Price;
use App\Models\Product;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use UnitEnum;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShoppingBag;

    protected static string|UnitEnum|null $navigationGroup = 'Menü';

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'ürün';

    protected static ?string $pluralModelLabel = 'Ürünler';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Grid::make(3)->columnSpanFull()->schema([
                Section::make('Ürün')->columnSpan(2)->columns(2)->schema([
                    TextInput::make('name')->label('Ad')->required()->maxLength(255)->columnSpanFull(),
                    Select::make('category_id')
                        ->label('Kategori')
                        ->relationship('category', 'name')
                        ->required()
                        ->preload(),
                    Price::input(),
                    Textarea::make('description')->label('Açıklama')->rows(3)->maxLength(1000)->columnSpanFull(),
                    CheckboxList::make('optionGroups')
                        ->label('Seçenek grupları')
                        ->helperText('Müşteri bu ürünü sipariş ederken seçebileceği gruplar.')
                        ->relationship('optionGroups', 'name')
                        ->columns(3)
                        ->columnSpanFull(),
                ]),
                Section::make('Görünüm')->columnSpan(1)->schema([
                    FileUpload::make('image_path')
                        ->label('Görsel')
                        ->image()
                        ->disk('public')
                        ->directory(Product::IMAGE_DIRECTORY)
                        ->visibility('public')
                        ->maxSize(4096)
                        ->helperText('JPG/PNG/WebP, en fazla 4 MB.'),
                    Toggle::make('is_active')->label('Menüde göster')->default(true),
                    Toggle::make('is_bestseller')->label('Çok satan'),
                    Toggle::make('is_new')->label('Yeni'),
                    TextInput::make('slug')
                        ->label('Kısa kod')
                        ->helperText('Boş bırakılırsa addan üretilir; sonradan değiştirilemez.')
                        ->maxLength(255)
                        ->alphaDash()
                        ->unique(ignoreRecord: true)
                        ->disabledOn('edit'),
                ]),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->reorderable('sort_order')
            ->defaultSort('sort_order')
            ->columns([
                ImageColumn::make('image')
                    ->label('')
                    ->getStateUsing(fn (Product $record) => $record->imageUrl())
                    ->square(),
                TextColumn::make('name')->label('Ad')->searchable()->description(fn (Product $record) => $record->slug),
                TextColumn::make('category.name')->label('Kategori')->sortable(),
                Price::column(),
                TextColumn::make('optionGroups.name')->label('Seçenekler')->badge()->toggleable(),
                ToggleColumn::make('is_active')->label('Aktif'),
                ToggleColumn::make('is_bestseller')->label('Çok satan')->toggleable(),
                ToggleColumn::make('is_new')->label('Yeni')->toggleable(),
            ])
            ->filters([
                SelectFilter::make('category_id')->label('Kategori')->relationship('category', 'name'),
                TernaryFilter::make('is_active')->label('Aktif'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListProducts::route('/'),
            'create' => CreateProduct::route('/create'),
            'edit' => EditProduct::route('/{record}/edit'),
        ];
    }
}
