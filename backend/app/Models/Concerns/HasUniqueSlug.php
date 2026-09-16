<?php

namespace App\Models\Concerns;

use Illuminate\Support\Str;

/**
 * Fills an empty `slug` on create from the model's slug source (e.g. name), keeping it unique.
 * Slugs are the public ids used by the API (e.g. tea_1), so they never change after creation.
 */
trait HasUniqueSlug
{
    protected static function bootHasUniqueSlug(): void
    {
        static::creating(function ($model) {
            if (filled($model->slug)) {
                return;
            }

            $base = Str::slug(Str::ascii((string) $model->slugSource()), '_') ?: Str::lower(class_basename($model));
            $base = Str::limit($base, 200, '');
            $slug = $base;
            $i = 2;
            while (static::query()->where('slug', $slug)->exists()) {
                $slug = "{$base}_{$i}";
                $i++;
            }
            $model->slug = $slug;
        });
    }

    protected function slugSource(): ?string
    {
        return $this->name;
    }
}
