<?php

namespace App\Models;

use App\Enums\OrderStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Order extends Model
{
    use LogsActivity;

    protected $fillable = ['cafe_table_id', 'status', 'note', 'has_new_items', 'total_cents', 'closed_at'];

    protected $attributes = [
        'status' => 'new',
        'note' => '',
        'has_new_items' => false,
        'total_cents' => 0,
    ];

    protected function casts(): array
    {
        return [
            'status' => OrderStatus::class,
            'has_new_items' => 'boolean',
            'total_cents' => 'integer',
            'closed_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Order $order) {
            $order->public_id ??= 'ord_'.Str::lower((string) Str::ulid());
        });
    }

    public function getActivitylogOptions(): LogOptions
    {
        // Status changes only; items/notes are customer content and already stored on the order.
        return LogOptions::defaults()->logOnly(['status'])->logOnlyDirty()->dontSubmitEmptyLogs();
    }

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }

    public function table(): BelongsTo
    {
        return $this->belongsTo(CafeTable::class, 'cafe_table_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class)->orderBy('id');
    }

    /** Orders that are still open (anything but done). */
    public function scopeOpen(Builder $query): void
    {
        $query->where('status', '!=', OrderStatus::Done->value);
    }

    public function recalculateTotal(): void
    {
        $this->total_cents = (int) $this->items()->sum(DB::raw('unit_price_cents * qty'));
    }
}
