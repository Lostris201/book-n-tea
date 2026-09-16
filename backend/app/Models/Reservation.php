<?php

namespace App\Models;

use App\Enums\ReservationSource;
use App\Enums\ReservationStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Reservation extends Model
{
    use LogsActivity;

    protected $fillable = [
        'source', 'external_provider', 'external_id', 'customer_name', 'customer_phone',
        'party_size', 'reserved_for', 'cafe_table_id', 'status', 'note',
    ];

    protected $attributes = [
        'source' => 'native',
        'status' => 'pending',
    ];

    protected function casts(): array
    {
        return [
            'source' => ReservationSource::class,
            'status' => ReservationStatus::class,
            'party_size' => 'integer',
            'reserved_for' => 'datetime',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        // Keep customer phone numbers out of the audit trail.
        return LogOptions::defaults()->logFillable()->logExcept(['customer_phone'])->logOnlyDirty()->dontSubmitEmptyLogs();
    }

    public function table(): BelongsTo
    {
        return $this->belongsTo(CafeTable::class, 'cafe_table_id');
    }

    public function isExternal(): bool
    {
        return $this->source === ReservationSource::External;
    }

    public function scopeForDay(Builder $query, \DateTimeInterface $day): void
    {
        $query->whereBetween('reserved_for', [
            \Illuminate\Support\Carbon::instance($day)->startOfDay(),
            \Illuminate\Support\Carbon::instance($day)->endOfDay(),
        ]);
    }
}
