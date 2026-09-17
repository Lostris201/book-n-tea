<?php

use Illuminate\Support\Facades\Schedule;

// Requires a cron entry: * * * * * php /path/to/backend/artisan schedule:run
Schedule::command('reservations:sync')->everyFiveMinutes()->withoutOverlapping()->runInBackground();
