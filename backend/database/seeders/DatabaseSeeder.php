<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    // Model events stay on: CafeTable generates qr_token in its creating hook.

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            MenuSeeder::class,
            CafeTableSeeder::class,
            SettingSeeder::class,
        ]);

        if (app()->environment('local', 'testing')) {
            $this->call(DevUserSeeder::class);
        }
    }
}
