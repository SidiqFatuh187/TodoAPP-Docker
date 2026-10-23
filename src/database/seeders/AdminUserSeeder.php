<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@claro.com'],
            [
                'name'              => 'Admin Wassup Task',
                'password'          => Hash::make('admin12345'),
                'role'              => 'admin',
                'is_banned'         => false,
                'email_verified_at' => now(),
                'timezone'          => 'Asia/Jakarta',
            ]
        );
    }
}

