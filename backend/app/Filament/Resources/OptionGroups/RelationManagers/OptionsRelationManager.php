<?php

namespace App\Filament\Resources\OptionGroups\RelationManagers;

use App\Filament\Support\Price;
use App\Models\Option;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;

class OptionsRelationManager extends RelationManager
{
    protected static string $relationship = 'options';

    protected static ?string $title = 'Seçenekler';

    protected static ?string $modelLabel = 'seçenek';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->label('Ad')->required()->maxLength(255),
            Price::input('price_cents', 'Ek ücret')->default(0),
            Toggle::make('is_active')->label('Aktif')->default(true),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->reorderable('sort_order')
            ->defaultSort('sort_order')
            ->columns([
                TextColumn::make('name')->label('Ad'),
                Price::column('price_cents', 'Ek ücret'),
                TextColumn::make('slug')->label('Kısa kod')->toggleable(isToggledHiddenByDefault: true),
                ToggleColumn::make('is_active')->label('Aktif'),
            ])
            ->headerActions([
                CreateAction::make()->mutateDataUsing(function (array $data): array {
                    $data['sort_order'] = (int) $this->getOwnerRecord()->options()->max('sort_order') + 1;

                    return $data;
                }),
            ])
            ->recordActions([
                EditAction::make(),
                // Past orders keep their own snapshot, so deleting an option is safe.
                DeleteAction::make(),
            ]);
    }
}
