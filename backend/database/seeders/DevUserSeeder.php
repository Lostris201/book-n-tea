<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Local development logins only. Never runs in production (see DatabaseSeeder).
 */
class DevUserSeeder extends Seeder
{
    public function run(): void
    {
        foreach (UserRole::cases() as $role) {
            User::updateOrCreate(
                ['email' => "{$role->value}@bookntea.test"],
                [
                    'name' => ucfirst($role->value),
                    'password' => 'password',
                    'role' => $role,
                    'is_active' => true,
                ],
            );
        }
    }
}
