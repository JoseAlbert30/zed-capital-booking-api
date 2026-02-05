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
                'email' => 'sleiman@zedcapitalbooking.com',
                'full_name' => 'Sleiman',
                'password' => 'sleiman123',
            ],
            [
                'email' => 'devi@zedcapitalbooking.com',
                'full_name' => 'Devi',
                'password' => 'devi123',
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
        $this->command->info('Email: sleiman@zedcapitalbooking.com | Password: sleiman123');
        $this->command->info('Email: devi@zedcapitalbooking.com | Password: devi123');
        $this->command->warn('Please change the passwords after first login!');
    }
}
