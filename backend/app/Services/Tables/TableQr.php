<?php

namespace App\Services\Tables;

use App\Models\CafeTable;
use chillerlan\QRCode\Common\EccLevel;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;

class TableQr
{
    /** Customer menu link printed on the table: {QR_MENU_URL}?t={qr_token} */
    public function url(CafeTable $table): string
    {
        $base = (string) (config('services.frontend.qr_menu_url') ?: config('app.url'));
        $separator = str_contains($base, '?') ? '&' : '?';

        return $base.$separator.'t='.urlencode($table->qr_token);
    }

    public function isConfigured(): bool
    {
        return filled(config('services.frontend.qr_menu_url'));
    }

    /** Inline SVG markup (generated locally, no external service). */
    public function svg(string $data): string
    {
        $options = new QROptions([
            'outputBase64' => false,
            'svgAddXmlHeader' => false,
            'eccLevel' => EccLevel::M,
            'addQuietzone' => true,
            'quietzoneSize' => 2,
        ]);

        return (new QRCode($options))->render($data);
    }
}
