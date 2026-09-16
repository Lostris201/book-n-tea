<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, LogsActivity, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'is_active',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        // Deactivating or demoting a user ends their API sessions immediately.
        static::updated(function (User $user) {
            if ($user->wasChanged(['is_active', 'role', 'password'])) {
                $user->tokens()->delete();
            }
        });
    }

    public function getActivitylogOptions(): LogOptions
    {
        // Never log password hashes or remember tokens.
        return LogOptions::defaults()->logOnly(['name', 'email', 'role', 'is_active'])->logOnlyDirty()->dontSubmitEmptyLogs();
    }

    public function hasRole(UserRole ...$roles): bool
    {
        return in_array($this->role, $roles, true);
    }

    public function isAdmin(): bool
    {
        return $this->is_active && $this->role === UserRole::Admin;
    }

    /** Admin or manager: menu, tables, orders, reservations. */
    public function canManage(): bool
    {
        return $this->is_active && $this->hasRole(UserRole::Admin, UserRole::Manager);
    }

    /** Staff use the order board, not the admin panel. */
    public function canAccessPanel(Panel $panel): bool
    {
        return $this->canManage();
    }
}
