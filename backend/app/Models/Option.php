<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Option extends Model
{
    protected $fillable = ['option_group_id', 'slug', 'name', 'price_cents', 'sort_order', 'is_active'];

    protected function casts(): array
    {
        return [
            'price_cents' => 'integer',
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(OptionGroup::class, 'option_group_id');
    }

    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }
}
