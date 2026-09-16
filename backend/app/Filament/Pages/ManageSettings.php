<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\DB;
use UnitEnum;

/**
 * @property-read Schema $form
 */
class ManageSettings extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static string|UnitEnum|null $navigationGroup = 'Yönetim';

    protected static ?int $navigationSort = 2;

    protected static ?string $navigationLabel = 'Ayarlar';

    protected static ?string $title = 'Kafe ayarları';

    protected static ?string $slug = 'settings';

    /** Setting keys edited on this page, with their defaults. */
    public const KEYS = [
        'cafe_name' => '',
        'slogan' => '',
        'phone' => '',
        'address' => '',
        'instagram' => '',
        'hours' => '',
        'call_waiter_enabled' => true,
        'request_bill_enabled' => true,
        'sound_notification' => true,
    ];

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    public static function canAccess(): bool
    {
        return (bool) Filament::auth()->user()?->isAdmin();
    }

    public function mount(): void
    {
        $stored = Setting::allAsArray();

        $this->form->fill(collect(self::KEYS)->map(fn ($default, $key) => $stored[$key] ?? $default)->all());
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Section::make('Kafe bilgileri')->columns(2)->schema([
                    TextInput::make('cafe_name')->label('Kafe adı')->required()->maxLength(100),
                    TextInput::make('slogan')->label('Slogan')->maxLength(200),
                    TextInput::make('phone')->label('Telefon')->tel()->maxLength(32),
                    TextInput::make('instagram')->label('Instagram')->maxLength(100)->placeholder('@booknteahouse'),
                    TextInput::make('address')->label('Adres')->maxLength(255)->columnSpanFull(),
                    TextInput::make('hours')->label('Çalışma saatleri')->maxLength(100)->columnSpanFull(),
                ]),
                Section::make('Özellikler')->columns(3)->schema([
                    Toggle::make('call_waiter_enabled')->label('Garson çağırma'),
                    Toggle::make('request_bill_enabled')->label('Hesap isteme'),
                    Toggle::make('sound_notification')->label('Panoda sesli bildirim'),
                ]),
            ]);
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Form::make([EmbeddedSchema::make('form')])
                ->id('form')
                ->livewireSubmitHandler('save')
                ->footer([
                    Actions::make([
                        Action::make('save')->label('Kaydet')->submit('save'),
                    ]),
                ]),
        ]);
    }

    public function save(): void
    {
        abort_unless(static::canAccess(), 403);

        $data = $this->form->getState();

        DB::transaction(function () use ($data) {
            foreach (array_keys(self::KEYS) as $key) {
                $value = $data[$key] ?? self::KEYS[$key];
                Setting::setValue($key, is_bool(self::KEYS[$key]) ? (bool) $value : (string) ($value ?? ''));
            }
        });

        Notification::make()->title('Ayarlar kaydedildi.')->success()->send();
    }
}
