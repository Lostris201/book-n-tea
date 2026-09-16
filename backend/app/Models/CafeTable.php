<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class CafeTable extends Model
{
    protected $fillable = ['number', 'name', 'is_active'];

    protected $hidden = ['qr_token'];

    protected function casts(): array
    {
        return [
            'number' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (CafeTable $table) {
            $table->qr_token ??= static::generateToken();
        });
    }

    public static function generateToken(): string
    {
        return Str::random(32);
    }

    public function regenerateToken(): void
    {
        $this->qr_token = static::generateToken();
        $this->save();
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function waiterCalls(): HasMany
    {
        return $this->hasMany(WaiterCall::class);
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class);
    }

    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }
}
