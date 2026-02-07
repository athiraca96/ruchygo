<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@ruchygo.com'],
            [
                'name' => 'Admin',
                'email' => 'admin@ruchygo.com',
                'password' => Hash::make('password'),
                'role' => 'admin',
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );

        $this->command->info('Admin user created:');
        $this->command->info('Email: admin@ruchygo.com');
        $this->command->info('Password: password');
    }
}
