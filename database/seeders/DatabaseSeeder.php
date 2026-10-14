<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::query()->firstOrCreate([
            'email' => "admin@admin.com",
        ], [
            'name' => "Admin",
            'password' => bcrypt("admin123456"),
            'role' => UserRole::ADMINISTRATOR,
            'is_active' => true,
        ]);
    }
}
