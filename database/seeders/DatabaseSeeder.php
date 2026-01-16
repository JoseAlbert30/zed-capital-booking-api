<?php

namespace Database\Seeders;

use App\Models\Property;
use App\Models\Unit;
use App\Models\User;
use App\Models\UserAttachment;
use App\Models\Booking;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create single property: Viera Residences
        $vieraResidences = Property::create([
            'project_name' => 'Viera Residences',
            'location' => 'Viera East, Cainta, Rizal',
        ]);

        // Create units for Viera Residences only (3-digit unit numbers)
        // Occupied units (with owners)
        $units = [
            ['property_id' => $vieraResidences->id, 'unit' => '101', 'status' => 'claimed', 'dewa_premise_number' => '685151093'],
            ['property_id' => $vieraResidences->id, 'unit' => '102', 'status' => 'claimed', 'dewa_premise_number' => '685151094'],
            ['property_id' => $vieraResidences->id, 'unit' => '103', 'status' => 'claimed', 'dewa_premise_number' => '685151095'],
            ['property_id' => $vieraResidences->id, 'unit' => '201', 'status' => 'claimed', 'dewa_premise_number' => '685151096'],
            ['property_id' => $vieraResidences->id, 'unit' => '202', 'status' => 'claimed', 'dewa_premise_number' => '685151097'],
            ['property_id' => $vieraResidences->id, 'unit' => '203', 'status' => 'claimed', 'dewa_premise_number' => '685151098'],
            ['property_id' => $vieraResidences->id, 'unit' => '301', 'status' => 'claimed', 'dewa_premise_number' => '685151099'],
            ['property_id' => $vieraResidences->id, 'unit' => '302', 'status' => 'claimed', 'dewa_premise_number' => '685151100'],
            ['property_id' => $vieraResidences->id, 'unit' => '303', 'status' => 'claimed', 'dewa_premise_number' => '685151101'],
            ['property_id' => $vieraResidences->id, 'unit' => '401', 'status' => 'claimed', 'dewa_premise_number' => '685151102'],
            ['property_id' => $vieraResidences->id, 'unit' => '402', 'status' => 'claimed', 'dewa_premise_number' => '685151103'],
            ['property_id' => $vieraResidences->id, 'unit' => '403', 'status' => 'claimed', 'dewa_premise_number' => '685151104'],
            ['property_id' => $vieraResidences->id, 'unit' => '501', 'status' => 'claimed', 'dewa_premise_number' => '685151105'],
            ['property_id' => $vieraResidences->id, 'unit' => '502', 'status' => 'claimed', 'dewa_premise_number' => '685151106'],
            ['property_id' => $vieraResidences->id, 'unit' => '503', 'status' => 'claimed', 'dewa_premise_number' => '685151107'],
        ];
        
        // Unoccupied units (no owners assigned)
        $unoccupiedUnits = [
            ['property_id' => $vieraResidences->id, 'unit' => '104', 'status' => 'unclaimed', 'dewa_premise_number' => '685151108'],
            ['property_id' => $vieraResidences->id, 'unit' => '105', 'status' => 'unclaimed', 'dewa_premise_number' => '685151109'],
            ['property_id' => $vieraResidences->id, 'unit' => '204', 'status' => 'unclaimed', 'dewa_premise_number' => '685151110'],
            ['property_id' => $vieraResidences->id, 'unit' => '205', 'status' => 'unclaimed', 'dewa_premise_number' => '685151111'],
            ['property_id' => $vieraResidences->id, 'unit' => '304', 'status' => 'unclaimed', 'dewa_premise_number' => '685151112'],
            ['property_id' => $vieraResidences->id, 'unit' => '305', 'status' => 'unclaimed', 'dewa_premise_number' => '685151113'],
            ['property_id' => $vieraResidences->id, 'unit' => '404', 'status' => 'unclaimed', 'dewa_premise_number' => '685151114'],
            ['property_id' => $vieraResidences->id, 'unit' => '405', 'status' => 'unclaimed', 'dewa_premise_number' => '685151115'],
            ['property_id' => $vieraResidences->id, 'unit' => '504', 'status' => 'unclaimed', 'dewa_premise_number' => '685151116'],
            ['property_id' => $vieraResidences->id, 'unit' => '505', 'status' => 'unclaimed', 'dewa_premise_number' => '685151117'],
        ];

        foreach ($units as $unitData) {
            Unit::create($unitData);
        }
        
        foreach ($unoccupiedUnits as $unitData) {
            Unit::create($unitData);
        }

        // Create admin user
        $admin = User::create([
            'full_name' => 'Admin User',
            'email' => 'admin@bookingsystem.com',
            'password' => Hash::make('admin123'),
            'email_verified_at' => now(),
            'payment_status' => 'pending', // Admin also has no payment
            'payment_date' => null,
            'mobile_number' => '+1-800-ADMIN-01',
            'has_mortgage' => false,
            'handover_ready' => false,
        ]);

        // Create 20 regular users
        $usersData = [
            ['full_name' => 'John Smith', 'email' => 'john.smith@example.com', 'mobile' => '+63-917-123-4501'],
            ['full_name' => 'Sarah Johnson', 'email' => 'sarah.johnson@example.com', 'mobile' => '+63-917-123-4502'],
            ['full_name' => 'Michael Brown', 'email' => 'michael.brown@example.com', 'mobile' => '+63-917-123-4503'],
            ['full_name' => 'Emily Davis', 'email' => 'emily.davis@example.com', 'mobile' => '+63-917-123-4504'],
            ['full_name' => 'David Wilson', 'email' => 'david.wilson@example.com', 'mobile' => '+63-917-123-4505'],
            ['full_name' => 'Jessica Martinez', 'email' => 'jessica.martinez@example.com', 'mobile' => '+63-917-123-4506'],
            ['full_name' => 'Christopher Garcia', 'email' => 'chris.garcia@example.com', 'mobile' => '+63-917-123-4507'],
            ['full_name' => 'Laura Rodriguez', 'email' => 'laura.rodriguez@example.com', 'mobile' => '+63-917-123-4508'],
            ['full_name' => 'Daniel Lee', 'email' => 'daniel.lee@example.com', 'mobile' => '+63-917-123-4509'],
            ['full_name' => 'Amanda White', 'email' => 'amanda.white@example.com', 'mobile' => '+63-917-123-4510'],
            ['full_name' => 'James Taylor', 'email' => 'james.taylor@example.com', 'mobile' => '+63-917-123-4511'],
            ['full_name' => 'Rachel Anderson', 'email' => 'rachel.anderson@example.com', 'mobile' => '+63-917-123-4512'],
            ['full_name' => 'Matthew Thomas', 'email' => 'matthew.thomas@example.com', 'mobile' => '+63-917-123-4513'],
            ['full_name' => 'Jennifer Jackson', 'email' => 'jennifer.jackson@example.com', 'mobile' => '+63-917-123-4514'],
            ['full_name' => 'Ryan White', 'email' => 'ryan.white@example.com', 'mobile' => '+63-917-123-4515'],
            ['full_name' => 'Megan Harris', 'email' => 'megan.harris@example.com', 'mobile' => '+63-917-123-4516'],
            ['full_name' => 'Brandon Martin', 'email' => 'brandon.martin@example.com', 'mobile' => '+63-917-123-4517'],
            ['full_name' => 'Stephanie Clark', 'email' => 'stephanie.clark@example.com', 'mobile' => '+63-917-123-4518'],
            ['full_name' => 'Kevin Lewis', 'email' => 'kevin.lewis@example.com', 'mobile' => '+63-917-123-4519'],
            ['full_name' => 'Nicole Walker', 'email' => 'nicole.walker@example.com', 'mobile' => '+63-917-123-4520'],
        ];

        $createdUsers = [];
        // Users with mortgages: indices 2, 5, 8, 11, 14, 17 (30% of users)
        $mortgageUserIndices = [2, 5, 8, 11, 14, 17];
        
        foreach ($usersData as $index => $userData) {
            $user = User::create([
                'full_name' => $userData['full_name'],
                'email' => $userData['email'],
                'password' => Hash::make('password123'),
                'email_verified_at' => now(),
                'payment_status' => 'pending', // All users have no payment
                'payment_date' => null,        // No payment date
                'mobile_number' => $userData['mobile'],
                'has_mortgage' => in_array($index, $mortgageUserIndices), // Some users have mortgages
                'handover_ready' => false,     // No one is ready yet
            ]);

            $createdUsers[] = $user;
        }

        // Assign units to users with realistic scenarios:
        // - Some users own multiple units
        // - Some units have joint ownership (2-3 buyers)
        // - Mix of single and joint ownership
        // Only get claimed units to assign to users
        $claimedUnits = Unit::where('status', 'claimed')->get();
        
        // Unit 101: User 0 (single owner)
        if ($claimedUnits->count() > 0 && count($createdUsers) > 0) {
            $claimedUnits[0]->users()->attach($createdUsers[0]->id, ['is_primary' => true]);
        }
        
        // Unit 102: User 0 (owns 2 units - this is their 2nd unit)
        if ($claimedUnits->count() > 1 && count($createdUsers) > 0) {
            $claimedUnits[1]->users()->attach($createdUsers[0]->id, ['is_primary' => true]);
        }
        
        // Unit 103: Users 1 & 2 (joint ownership - 2 buyers)
        if ($claimedUnits->count() > 2 && count($createdUsers) > 2) {
            $claimedUnits[2]->users()->attach($createdUsers[1]->id, ['is_primary' => true]);
            $claimedUnits[2]->users()->attach($createdUsers[2]->id, ['is_primary' => false]);
        }
        
        // Unit 201: Users 3, 4, 5 (joint ownership - 3 buyers)
        if ($claimedUnits->count() > 3 && count($createdUsers) > 5) {
            $claimedUnits[3]->users()->attach($createdUsers[3]->id, ['is_primary' => true]);
            $claimedUnits[3]->users()->attach($createdUsers[4]->id, ['is_primary' => false]);
            $claimedUnits[3]->users()->attach($createdUsers[5]->id, ['is_primary' => false]);
        }
        
        // Unit 202: User 6 (single owner)
        if ($claimedUnits->count() > 4 && count($createdUsers) > 6) {
            $claimedUnits[4]->users()->attach($createdUsers[6]->id, ['is_primary' => true]);
        }
        
        // Unit 203: User 6 (owns 2 units - this is their 2nd unit)
        if ($claimedUnits->count() > 5 && count($createdUsers) > 6) {
            $claimedUnits[5]->users()->attach($createdUsers[6]->id, ['is_primary' => true]);
        }
        
        // Unit 301: Users 7 & 8 (joint ownership - 2 buyers)
        if ($claimedUnits->count() > 6 && count($createdUsers) > 8) {
            $claimedUnits[6]->users()->attach($createdUsers[7]->id, ['is_primary' => true]);
            $claimedUnits[6]->users()->attach($createdUsers[8]->id, ['is_primary' => false]);
        }
        
        // Unit 302: User 9 (single owner)
        if ($claimedUnits->count() > 7 && count($createdUsers) > 9) {
            $claimedUnits[7]->users()->attach($createdUsers[9]->id, ['is_primary' => true]);
        }
        
        // Unit 303: Users 10 & 11 (joint ownership - 2 buyers, User 10 owns multiple units)
        if ($claimedUnits->count() > 8 && count($createdUsers) > 11) {
            $claimedUnits[8]->users()->attach($createdUsers[10]->id, ['is_primary' => true]);
            $claimedUnits[8]->users()->attach($createdUsers[11]->id, ['is_primary' => false]);
        }
        
        // Unit 401: User 10 (owns 2 units - this is their 2nd unit, single owner)
        if ($claimedUnits->count() > 9 && count($createdUsers) > 10) {
            $claimedUnits[9]->users()->attach($createdUsers[10]->id, ['is_primary' => true]);
        }
        
        // Unit 402: Users 12 & 13 (joint ownership - 2 buyers)
        if ($claimedUnits->count() > 10 && count($createdUsers) > 13) {
            $claimedUnits[10]->users()->attach($createdUsers[12]->id, ['is_primary' => true]);
            $claimedUnits[10]->users()->attach($createdUsers[13]->id, ['is_primary' => false]);
        }
        
        // Unit 403: Users 14, 15, 16 (joint ownership - 3 buyers)
        if ($claimedUnits->count() > 11 && count($createdUsers) > 16) {
            $claimedUnits[11]->users()->attach($createdUsers[14]->id, ['is_primary' => true]);
            $claimedUnits[11]->users()->attach($createdUsers[15]->id, ['is_primary' => false]);
            $claimedUnits[11]->users()->attach($createdUsers[16]->id, ['is_primary' => false]);
        }
        
        // Unit 501: User 17 (single owner)
        if ($claimedUnits->count() > 12 && count($createdUsers) > 17) {
            $claimedUnits[12]->users()->attach($createdUsers[17]->id, ['is_primary' => true]);
        }
        
        // Unit 502: User 18 (single owner)
        if ($claimedUnits->count() > 13 && count($createdUsers) > 18) {
            $claimedUnits[13]->users()->attach($createdUsers[18]->id, ['is_primary' => true]);
        }
        
        // Unit 503: User 19 (single owner)
        if ($claimedUnits->count() > 14 && count($createdUsers) > 19) {
            $claimedUnits[14]->users()->attach($createdUsers[19]->id, ['is_primary' => true]);
        }

        // Note: Units with status 'unclaimed' remain unassigned and available

        // NO bookings created - users have no bookings

        $this->command->info('✅ Database seeded successfully!');
        $this->command->info('');
        $this->command->info('📊 Summary:');
        $this->command->info('  - Properties: 1 (Viera Residences only)');
        $this->command->info('  - Units: ' . Unit::count() . ' (3-digit unit numbers: 101-503)');
        $this->command->info('  - Users: ' . User::count() . ' (1 admin + 20 regular)');
        $this->command->info('  - Users with mortgages: ' . User::where('has_mortgage', true)->count() . ' (30%)');
        $this->command->info('  - Multi-unit owners: 3 users own 2 units each');
        $this->command->info('  - Joint ownership: Some units with 2-3 co-buyers');
        $this->command->info('  - All users have units assigned but no payments, SOA, or bookings');
        $this->command->info('  - Bookings: 0 (clean slate)');
        $this->command->info('  - Attachments: 0 (no SOA files)');
        $this->command->info('  - Handover ready: 0 (no documents uploaded yet)');
        $this->command->info('');
        $this->command->info('🔑 Admin Credentials:');
        $this->command->info('  Email: admin@bookingsystem.com');
        $this->command->info('  Password: admin123');
        $this->command->info('');
        $this->command->info('👥 Sample Users (password: password123):');
        $this->command->info('  - john.smith@example.com (owns units 101 & 102)');
        $this->command->info('  - sarah.johnson@example.com (joint buyer in unit 103)');
        $this->command->info('  - michael.brown@example.com (co-buyer in unit 103, HAS MORTGAGE)');
        $this->command->info('  - emily.davis@example.com (primary buyer in unit 201, 3 co-buyers)');
        $this->command->info('  - david.wilson@example.com (owns units 202 & 203)');
        $this->command->info('  - matthew.thomas@example.com (owns units 303 & 401)');
        $this->command->info('  - jessica.martinez@example.com (HAS MORTGAGE)');
        $this->command->info('  - daniel.lee@example.com (HAS MORTGAGE)');
    }
}
