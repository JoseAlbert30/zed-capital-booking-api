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
        // Create admin users
        $adminUsers = [
            [
                'email' => 'admin@bookingsystem.com',
                'full_name' => 'System Administrator',
                'password' => 'admin123',
            ],
            [
                'email' => 'mohamad@zedcapitalbooking.com',
                'full_name' => 'Mohamad',
                'password' => 'mohamad123',
            ],
            [
                'email' => 'mayada@zedcapitalbooking.com',
                'full_name' => 'Mayada',
                'password' => 'mayada123',
            ],
        ];

        foreach ($adminUsers as $admin) {
            User::updateOrCreate(
                ['email' => $admin['email']],
                [
                    'full_name' => $admin['full_name'],
                    'email' => $admin['email'],
                    'password' => Hash::make($admin['password']),
                    'payment_status' => 'fully_paid',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
            $this->command->info("Admin user created: {$admin['email']}");
        }

        $this->command->info('\nAdmin users created successfully!');
        $this->command->info('Email: admin@bookingsystem.com | Password: admin123');
        $this->command->info('Email: mohamad@zedcapitalbooking.com | Password: mohamad123');
        $this->command->info('Email: mayada@zedcapitalbooking.com | Password: mayada123');
        $this->command->warn('Please change the passwords after first login!');
    }
}
