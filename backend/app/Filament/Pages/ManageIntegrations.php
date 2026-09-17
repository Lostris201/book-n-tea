<?php

namespace App\Filament\Pages;

use App\Integrations\Reservations\ReservationProviderManager;
use App\Integrations\Reservations\ReservationSync;
use App\Support\LogRedactor;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Log;
use Throwable;
use UnitEnum;

class ManageIntegrations extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowsRightLeft;

    protected static string|UnitEnum|null $navigationGroup = 'Yönetim';

    protected static ?int $navigationSort = 4;

    protected static ?string $navigationLabel = 'Entegrasyonlar';

    protected static ?string $title = 'Rezervasyon entegrasyonu';

    protected static ?string $slug = 'integrations';

    public static function canAccess(): bool
    {
        return (bool) Filament::auth()->user()?->isAdmin();
    }

    private function manager(): ReservationProviderManager
    {
        return app(ReservationProviderManager::class);
    }

    protected function getHeaderActions(): array
    {
        $manager = $this->manager();

        return [
            Action::make('enable')
                ->label('Etkinleştir')
                ->icon(Heroicon::OutlinedPlay)
                ->requiresConfirmation()
                ->modalDescription('Harici rezervasyonlar içe aktarılır ve paneldeki yeni rezervasyonlar sağlayıcıya gönderilir.')
                ->visible(fn () => $manager->isConfigured() && ! $manager->isEnabled())
                ->action(function () use ($manager) {
                    $this->authorizeAdmin();
                    $manager->integration()->update(['is_enabled' => true]);
                    Notification::make()->title('Entegrasyon etkinleştirildi.')->success()->send();
                }),
            Action::make('disable')
                ->label('Devre dışı bırak')
                ->icon(Heroicon::OutlinedPause)
                ->color('danger')
                ->requiresConfirmation()
                ->visible(fn () => $manager->isEnabled())
                ->action(function () use ($manager) {
                    $this->authorizeAdmin();
                    $manager->integration()->update(['is_enabled' => false]);
                    Notification::make()->title('Entegrasyon devre dışı bırakıldı.')->success()->send();
                }),
            Action::make('testConnection')
                ->label('Bağlantıyı test et')
                ->icon(Heroicon::OutlinedSignal)
                ->color('gray')
                ->visible(fn () => $manager->isConfigured())
                ->action(function () use ($manager) {
                    $this->authorizeAdmin();
                    $this->testConnection($manager);
                }),
            Action::make('syncNow')
                ->label('Şimdi senkronize et')
                ->icon(Heroicon::OutlinedArrowPath)
                ->visible(fn () => $manager->isEnabled())
                ->action(function () use ($manager) {
                    $this->authorizeAdmin();
                    $this->syncNow($manager);
                }),
        ];
    }

    public function content(Schema $schema): Schema
    {
        $manager = $this->manager();
        $configured = $manager->configured();
        $integration = $manager->integration();
        $config = config('services.reservation');

        $set = fn (?string $value) => filled($value) ? 'Ayarlı' : 'Ayarlı değil';

        return $schema->components([
            Section::make('Sağlayıcı')
                ->description('Anahtarlar yalnızca sunucudaki .env dosyasında tutulur ve burada gösterilmez.')
                ->columns(2)
                ->schema([
                    TextEntry::make('provider')->label('Sağlayıcı')->state(match (true) {
                        $manager->misconfiguredName() !== null => "Bilinmeyen sağlayıcı: {$manager->misconfiguredName()}",
                        $manager->isConfigured() => $configured->label(),
                        default => 'Yapılandırılmamış (yalnızca panel rezervasyonları)',
                    }),
                    TextEntry::make('status')
                        ->label('Durum')
                        ->badge()
                        ->state($manager->isEnabled() ? 'Etkin' : 'Kapalı')
                        ->color($manager->isEnabled() ? 'success' : 'gray'),
                    TextEntry::make('api_host')->label('API adresi')->state(parse_url((string) ($config['base_url'] ?? ''), PHP_URL_HOST) ?: '—'),
                    TextEntry::make('api_key')->label('API anahtarı')->state($set($config['key'] ?? null)),
                    TextEntry::make('api_secret')->label('API gizli anahtarı')->state($set($config['secret'] ?? null)),
                    TextEntry::make('webhook_secret')->label('Webhook imza anahtarı')->state($set($config['webhook_secret'] ?? null)),
                    TextEntry::make('capabilities')->label('Özellikler')->state(implode(', ', array_filter([
                        $configured->supportsWebhooks() ? 'Webhook' : null,
                        $configured->supportsPush() ? 'Rezervasyon gönderme' : null,
                        'Periyodik senkronizasyon (5 dk)',
                    ]))),
                    TextEntry::make('webhook_url')
                        ->label('Webhook adresi (sağlayıcıya verin)')
                        ->state($manager->isConfigured() && $configured->supportsWebhooks()
                            ? url('/api/webhooks/reservations/'.$configured->name())
                            : '—')
                        ->copyable(),
                ]),
            Section::make('Son durum')
                ->columns(2)
                ->schema([
                    TextEntry::make('last_synced_at')
                        ->label('Son senkronizasyon')
                        ->state($integration?->last_synced_at?->format('d.m.Y H:i') ?? 'Henüz yok'),
                    TextEntry::make('last_error')
                        ->label('Son hata')
                        ->state($integration?->last_error ?: 'Yok')
                        ->color($integration?->last_error ? 'danger' : null)
                        ->columnSpanFull(),
                ]),
        ]);
    }

    private function testConnection(ReservationProviderManager $manager): void
    {
        try {
            $ok = $manager->configured()->testConnection();
        } catch (Throwable $e) {
            $ok = false;
            Log::channel('integrations')->warning('Connection test threw', ['message' => LogRedactor::redactString($e->getMessage())]);
        }

        if ($ok) {
            Notification::make()->title('Bağlantı başarılı.')->success()->send();

            return;
        }

        Log::channel('security')->warning('Integration connection test failed', ['provider' => $manager->configuredName()]);
        Notification::make()->title('Bağlantı kurulamadı.')->body('Adres ve anahtarları .env dosyasında kontrol edin.')->danger()->send();
    }

    private function syncNow(ReservationProviderManager $manager): void
    {
        try {
            $count = app(ReservationSync::class)->syncWindow(
                now()->startOfDay()->subDay(),
                now()->addDays((int) config('services.reservation.sync_days', 30))->endOfDay(),
            );
            Notification::make()->title("{$count} rezervasyon senkronize edildi.")->success()->send();
        } catch (Throwable) {
            Notification::make()->title('Senkronizasyon başarısız.')->body('Ayrıntılar “Son hata” alanında.')->danger()->send();
        }
    }

    private function authorizeAdmin(): void
    {
        abort_unless(static::canAccess(), 403);
    }
}
