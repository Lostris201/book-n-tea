<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    private const DEFAULTS = [
        'cafe_name' => 'Book N Tea',
        'slogan' => 'A sanctuary for book lovers & tea connoisseurs',
        'phone' => '+90 212 555 01 23',
        'address' => 'Kütüphane Cad. No: 42, Moda / Kadıköy',
        'instagram' => '@booknteahouse',
        'hours' => 'Hergün: 08:30 - 23:30',
        'call_waiter_enabled' => true,
        'request_bill_enabled' => true,
        'sound_notification' => true,
    ];

    public function run(): void
    {
        // Only fill missing keys; never overwrite values the café has edited.
        foreach (self::DEFAULTS as $key => $value) {
            Setting::firstOrCreate(['key' => $key], ['value' => $value]);
        }
    }
}
