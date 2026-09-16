<?php

namespace App\Filament\Resources\CafeTables\Pages;

use App\Filament\Resources\CafeTables\CafeTableResource;
use App\Models\CafeTable;
use App\Services\Tables\TableQr;
use Filament\Actions\Action;
use Filament\Resources\Pages\Page;
use Filament\Support\Icons\Heroicon;

class PrintTableQrCodes extends Page
{
    protected static string $resource = CafeTableResource::class;

    protected string $view = 'filament.resources.cafe-tables.print-qr-codes';

    protected static ?string $title = 'Masa QR kodları';

    protected function getHeaderActions(): array
    {
        return [
            Action::make('print')
                ->label('Yazdır')
                ->icon(Heroicon::OutlinedPrinter)
                ->alpineClickHandler('window.print()'),
        ];
    }

    /** @return list<array{name: string, url: string, svg: string}> */
    public function getCards(): array
    {
        $qr = app(TableQr::class);

        return CafeTable::active()->orderBy('number')->get()
            ->map(function (CafeTable $table) use ($qr) {
                $url = $qr->url($table);

                return ['name' => $table->name, 'url' => $url, 'svg' => $qr->svg($url)];
            })
            ->all();
    }

    public function isMenuUrlConfigured(): bool
    {
        return app(TableQr::class)->isConfigured();
    }
}
