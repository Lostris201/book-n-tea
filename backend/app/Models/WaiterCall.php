<?php

namespace App\Models;

use App\Enums\WaiterCallType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WaiterCall extends Model
{
    protected $fillable = ['cafe_table_id', 'type', 'reason', 'resolved_at'];

    protected function casts(): array
    {
        return [
            'type' => WaiterCallType::class,
            'resolved_at' => 'datetime',
        ];
    }

    public function table(): BelongsTo
    {
        return $this->belongsTo(CafeTable::class, 'cafe_table_id');
    }

    public function scopePending(Builder $query): void
    {
        $query->whereNull('resolved_at');
    }
}
