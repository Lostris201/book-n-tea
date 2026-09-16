<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Integration extends Model
{
    protected $fillable = ['provider', 'is_enabled', 'config', 'last_synced_at', 'last_error'];

    // Never serialize credentials.
    protected $hidden = ['config'];

    protected function casts(): array
    {
        return [
            'is_enabled' => 'boolean',
            'config' => 'encrypted:array',
            'last_synced_at' => 'datetime',
        ];
    }
}
