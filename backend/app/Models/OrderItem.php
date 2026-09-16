<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends Model
{
    protected $fillable = [
        'order_id', 'product_id', 'name_snapshot', 'options_snapshot',
        'unit_price_cents', 'qty', 'is_new',
    ];

    protected function casts(): array
    {
        return [
            'options_snapshot' => 'array',
            'unit_price_cents' => 'integer',
            'qty' => 'integer',
            'is_new' => 'boolean',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function lineTotalCents(): int
    {
        return $this->unit_price_cents * $this->qty;
    }
}
