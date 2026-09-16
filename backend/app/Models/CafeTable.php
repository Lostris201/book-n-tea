<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class CafeTable extends Model
{
    use LogsActivity;

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

    public function getActivitylogOptions(): LogOptions
    {
        // The QR token is a secret: log that it changed, never its value.
        return LogOptions::defaults()->logOnly(['number', 'name', 'is_active'])->logOnlyDirty()->dontSubmitEmptyLogs();
    }

    public static function generateToken(): string
    {
        return Str::random(32);
    }

    /** Invalidates the printed QR code for this table. */
    public function regenerateToken(): void
    {
        $this->qr_token = static::generateToken();
        $this->save();

        activity()
            ->performedOn($this)
            ->event('updated')
            ->withProperties(['qr_token_regenerated' => true])
            ->log('QR token regenerated');
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
