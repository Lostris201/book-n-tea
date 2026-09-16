<?php

namespace App\Models;

use App\Models\Concerns\HasUniqueSlug;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Product extends Model
{
    use HasUniqueSlug, LogsActivity;

    /** Uploaded images live on the public disk under this directory. */
    public const IMAGE_DIRECTORY = 'products';

    protected $fillable = [
        'category_id', 'slug', 'name', 'description', 'image_path', 'price_cents',
        'is_active', 'is_bestseller', 'is_new', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'price_cents' => 'integer',
            'is_active' => 'boolean',
            'is_bestseller' => 'boolean',
            'is_new' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty()->dontSubmitEmptyLogs();
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function optionGroups(): BelongsToMany
    {
        return $this->belongsToMany(OptionGroup::class, 'product_option_group')->withTimestamps();
    }

    /**
     * Public image URL: uploads → absolute storage URL; seeded legacy paths (assets/...) and
     * absolute URLs are returned unchanged.
     */
    public function imageUrl(): ?string
    {
        if (blank($this->image_path)) {
            return null;
        }

        if (Str::startsWith($this->image_path, self::IMAGE_DIRECTORY.'/')) {
            return Storage::disk('public')->url($this->image_path);
        }

        return $this->image_path;
    }

    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    public function scopeOrdered(Builder $query): void
    {
        $query->orderBy('sort_order')->orderBy('id');
    }
}
