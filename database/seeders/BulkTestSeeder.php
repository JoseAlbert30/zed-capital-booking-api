<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Property;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class BulkTestSeeder extends Seeder
{
    /**
     * Run the database seeder.
     */
    public function run(): void
    {
        // Get Viera Residences property
        $property = Property::where('project_name', 'Viera Residences')->first();
        
        if (!$property) {
            $this->command->error('Viera Residences property not found. Please run PropertySeeder first.');
            return;
        }

        $this->command->info('Starting bulk test data generation...');

        $passportPrefixes = ['AB', 'CD', 'EF', 'GH', 'JK', 'LM', 'NP', 'QR', 'ST', 'UV', 'WX', 'YZ'];
        $userCounter = 1;

        // Create 100 units (18 units per floor, starting from floor 1)
        for ($i = 1; $i <= 100; $i++) {
            $floor = (int)(($i - 1) / 18) + 1; // 18 units per floor
            $unitNumOnFloor = (($i - 1) % 18) + 1; // Unit 01-18 per floor
            $unitNumber = ($floor * 100) + $unitNumOnFloor;
            
            $unit = Unit::create([
                'property_id' => $property->id,
                'unit' => (string) $unitNumber,
                'status' => 'claimed',
                'dewa_premise_number' => '685' . str_pad($i, 6, '0', STR_PAD_LEFT),
            ]);

            // Create 1-2 owners per unit
            $ownersCount = ($i % 3 === 0) ? 2 : 1; // Every 3rd unit has 2 owners
            
            for ($j = 0; $j < $ownersCount; $j++) {
                $user = User::create([
                    'full_name' => 'Test User ' . $userCounter,
                    'email' => 'user' . $userCounter . '@example.com',
                    'password' => Hash::make('password123'),
                    'email_verified_at' => now(),
                    'payment_status' => 'pending',
                    'payment_date' => null,
                    'mobile_number' => '+63-917-' . str_pad($userCounter, 7, '0', STR_PAD_LEFT),
                    'passport_number' => $passportPrefixes[$userCounter % count($passportPrefixes)] . str_pad($userCounter, 6, '0', STR_PAD_LEFT),
                    'has_mortgage' => false,
                    'handover_ready' => false,
                ]);
                
                $isPrimary = ($j === 0);
                $unit->users()->attach($user->id, ['is_primary' => $isPrimary]);
                
                $userCounter++;
            }
            
            if ($i % 20 === 0) {
                $this->command->info("Created {$i} units...");
            }
        }

        $totalUsers = $userCounter - 1;
        $this->command->info('✓ Bulk test data generation completed!');
        $this->command->info('Total units: 100 (18 units per floor)');
        $this->command->info("Total users created: {$totalUsers} (owners/co-owners)");
    }
}
