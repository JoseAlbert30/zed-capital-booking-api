<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create admin user
        User::updateOrCreate(
            ['email' => 'admin@bookingsystem.com'],
            [
                'full_name' => 'System Administrator',
                'email' => 'admin@bookingsystem.com',
                'password' => Hash::make('admin123'), // Change this password after first login
                'payment_status' => 'fully_paid',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        $this->command->info('Admin user created successfully!');
        $this->command->info('Email: admin@bookingsystem.com');
        $this->command->info('Password: admin123');
        $this->command->warn('Please change the password after first login!');
    }
}
