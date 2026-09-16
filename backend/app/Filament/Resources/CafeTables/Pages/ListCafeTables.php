<?php

namespace App\Filament\Resources\CafeTables\Pages;

use App\Filament\Resources\CafeTables\CafeTableResource;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;

class ListCafeTables extends ListRecords
{
    protected static string $resource = CafeTableResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('printQr')
                ->label('QR kodlarını yazdır')
                ->icon(Heroicon::OutlinedPrinter)
                ->color('gray')
                ->url(CafeTableResource::getUrl('qr')),
            CreateAction::make(),
        ];
    }
}
