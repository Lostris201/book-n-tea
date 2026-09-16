<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OptionGroup extends Model
{
    protected $fillable = ['key', 'name', 'is_multi_select'];

    protected function casts(): array
    {
        return [
            'is_multi_select' => 'boolean',
        ];
    }

    public function options(): HasMany
    {
        return $this->hasMany(Option::class)->orderBy('sort_order')->orderBy('id');
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'product_option_group')->withTimestamps();
    }
}
