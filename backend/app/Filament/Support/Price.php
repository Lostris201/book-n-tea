<?php

namespace App\Filament\Support;

use App\Support\Money;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\TextColumn;

/**
 * Money is stored as integer kuruş; the panel shows and accepts TL.
 */
final class Price
{
    public static function input(string $name = 'price_cents', string $label = 'Fiyat'): TextInput
    {
        return TextInput::make($name)
            ->label($label)
            ->suffix('₺')
            ->numeric()
            ->minValue(0)
            ->maxValue(1_000_000)
            ->step(0.01)
            ->formatStateUsing(fn ($state) => $state === null ? null : number_format(((int) $state) / 100, 2, '.', ''))
            ->dehydrateStateUsing(fn ($state) => Money::fromTl($state));
    }

    public static function column(string $name = 'price_cents', string $label = 'Fiyat'): TextColumn
    {
        return TextColumn::make($name)
            ->label($label)
            ->formatStateUsing(fn ($state) => self::format((int) $state))
            ->alignEnd();
    }

    public static function format(int $cents): string
    {
        return number_format($cents / 100, 2, ',', '.').' ₺';
    }
}
