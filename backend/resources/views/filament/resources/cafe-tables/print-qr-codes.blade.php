<x-filament-panels::page>
    @unless ($this->isMenuUrlConfigured())
        <div class="bnt-qr-warning">
            <strong>QR_MENU_URL ayarlanmamış.</strong>
            Kodlar şimdilik <code>{{ config('app.url') }}</code> adresini gösteriyor. Yazdırmadan önce
            <code>.env</code> dosyasında müşteri menüsünün adresini ayarlayın.
        </div>
    @endunless

    <div class="bnt-qr-grid">
        @forelse ($this->getCards() as $card)
            <article class="bnt-qr-card">
                <p class="bnt-qr-card__eyebrow">Book n Tea</p>
                <p class="bnt-qr-card__name">{{ $card['name'] }}</p>
                {{-- SVG is generated server-side by chillerlan/php-qrcode from our own URL. --}}
                <div class="bnt-qr-card__code">{!! $card['svg'] !!}</div>
                <p class="bnt-qr-card__hint">Menü ve sipariş için okutun</p>
            </article>
        @empty
            <p>Aktif masa yok.</p>
        @endforelse
    </div>

    <style>
        .bnt-qr-warning {
            border: 1px solid rgb(245 158 11 / .5);
            background: rgb(245 158 11 / .1);
            border-radius: .75rem;
            padding: .75rem 1rem;
            font-size: .875rem;
        }
        .bnt-qr-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(14rem, 1fr));
            gap: 1rem;
        }
        .bnt-qr-card {
            background: #fff;
            color: #1c1917;
            border: 1px solid #e7e5e4;
            border-radius: .75rem;
            padding: 1.25rem 1rem;
            text-align: center;
        }
        .bnt-qr-card__eyebrow { font-size: .75rem; letter-spacing: .15em; text-transform: uppercase; color: #78716c; }
        .bnt-qr-card__name { font-size: 1.5rem; font-weight: 600; margin: .25rem 0 .5rem; }
        .bnt-qr-card__code svg { width: 100%; height: auto; max-width: 14rem; margin: 0 auto; display: block; }
        .bnt-qr-card__hint { font-size: .8rem; color: #57534e; margin-top: .5rem; }

        @media print {
            .fi-sidebar, .fi-topbar, .fi-header, .bnt-qr-warning { display: none !important; }
            .fi-main, .fi-page, .fi-main-ctn { padding: 0 !important; margin: 0 !important; max-width: none !important; }
            body { background: #fff !important; }
            .bnt-qr-grid { grid-template-columns: 1fr; gap: 0; }
            .bnt-qr-card { border: none; page-break-after: always; break-after: page; padding-top: 3cm; }
            .bnt-qr-card__code svg { max-width: 10cm; }
        }
    </style>
</x-filament-panels::page>
