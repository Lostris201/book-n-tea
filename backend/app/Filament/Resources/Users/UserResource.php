<?php

namespace App\Filament\Resources\Users;

use App\Enums\UserRole;
use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Models\User;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Validation\Rules\Password;
use UnitEnum;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static string|UnitEnum|null $navigationGroup = 'Yönetim';

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'kullanıcı';

    protected static ?string $pluralModelLabel = 'Kullanıcılar';

    protected static ?string $recordTitleAttribute = 'name';

    public const ROLE_LABELS = [
        'admin' => 'Yönetici (tam yetki)',
        'manager' => 'Müdür (menü, masa, sipariş, rezervasyon)',
        'staff' => 'Personel (sipariş panosu)',
    ];

    public static function form(Schema $schema): Schema
    {
        $isSelf = fn (?User $record) => $record !== null && $record->is(Filament::auth()->user());

        return $schema->components([
            Section::make()->columns(2)->columnSpanFull()->schema([
                TextInput::make('name')->label('Ad soyad')->required()->maxLength(255),
                TextInput::make('email')->label('E-posta')->email()->required()->maxLength(255)->unique(ignoreRecord: true),
                Select::make('role')
                    ->label('Rol')
                    ->options(self::ROLE_LABELS)
                    ->default(UserRole::Staff->value)
                    ->required()
                    // Admins can't demote themselves (would lock them out mid-session).
                    ->disabled($isSelf),
                Toggle::make('is_active')
                    ->label('Aktif')
                    ->helperText('Pasif kullanıcılar giriş yapamaz; açık oturumları kapatılır.')
                    ->default(true)
                    ->inline(false)
                    ->disabled($isSelf),
                TextInput::make('password')
                    ->label('Şifre')
                    ->password()
                    ->revealable()
                    ->rule(Password::min(10))
                    ->required(fn (string $operation) => $operation === 'create')
                    ->dehydrated(fn (?string $state) => filled($state))
                    ->helperText(fn (string $operation) => $operation === 'edit' ? 'Değiştirmek istemiyorsanız boş bırakın.' : 'En az 10 karakter.'),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('name')
            ->columns([
                TextColumn::make('name')->label('Ad soyad')->searchable(),
                TextColumn::make('email')->label('E-posta')->searchable(),
                TextColumn::make('role')
                    ->label('Rol')
                    ->badge()
                    ->formatStateUsing(fn (UserRole $state) => match ($state) {
                        UserRole::Admin => 'Yönetici',
                        UserRole::Manager => 'Müdür',
                        UserRole::Staff => 'Personel',
                    })
                    ->color(fn (UserRole $state) => match ($state) {
                        UserRole::Admin => 'danger',
                        UserRole::Manager => 'warning',
                        UserRole::Staff => 'gray',
                    }),
                IconColumn::make('is_active')->label('Aktif')->boolean(),
                TextColumn::make('created_at')->label('Eklendi')->date('d.m.Y')->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('role')->label('Rol')->options(self::ROLE_LABELS),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListUsers::route('/'),
            'create' => CreateUser::route('/create'),
            'edit' => EditUser::route('/{record}/edit'),
        ];
    }
}
