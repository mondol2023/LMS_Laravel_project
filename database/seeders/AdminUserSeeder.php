<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::query()->firstOrCreate(
            ['email' => 'englishtherapy@example.com'],
            [
                'name' => 'Admin User',
                'password' => 'english1234',
                'role' => 'admin',
                'roll_number' => '123456',
                'email_verified_at' => now(),
            ]
        );
    }
}
