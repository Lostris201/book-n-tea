<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Integration extends Model
{
    use LogsActivity;

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

    protected static function booted(): void
    {
        // Record that credentials/config changed — never the values.
        static::saved(function (Integration $integration) {
            if ($integration->wasChanged('config') || ($integration->wasRecentlyCreated && filled($integration->config))) {
                activity()
                    ->performedOn($integration)
                    ->event('updated')
                    ->withProperties(['config_changed' => true])
                    ->log('Integration config changed');
            }
        });
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logOnly(['provider', 'is_enabled'])->logOnlyDirty()->dontSubmitEmptyLogs();
    }
}
