<?php

namespace Database\Seeders;

use App\Models\CafeTable;
use Illuminate\Database\Seeder;

class CafeTableSeeder extends Seeder
{
    public const TABLE_COUNT = 12;

    public function run(): void
    {
        for ($number = 1; $number <= self::TABLE_COUNT; $number++) {
            // firstOrCreate keeps existing QR tokens on re-seed, so printed QR codes stay valid.
            CafeTable::firstOrCreate(
                ['number' => $number],
                ['name' => sprintf('Masa %02d', $number), 'is_active' => true],
            );
        }
    }
}
