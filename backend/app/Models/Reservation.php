<?php

namespace App\Models;

use App\Enums\ReservationSource;
use App\Enums\ReservationStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Reservation extends Model
{
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

    public function table(): BelongsTo
    {
        return $this->belongsTo(CafeTable::class, 'cafe_table_id');
    }
}
