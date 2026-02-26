<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Unit;
use App\Models\FinanceEmail;

class GateElevenFinanceEmailsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get all Gate Eleven Residences units
        $units = Unit::whereHas('property', function($query) {
            $query->where('project_name', 'Gate Eleven Residences');
        })->with('property')->get();

        if ($units->isEmpty()) {
            $this->command->info('No Gate Eleven Residences units found.');
            return;
        }

        $this->command->info("Found {$units->count()} Gate Eleven units.");

        foreach ($units as $unit) {
            // Check if finance email already exists
            $existingEmail = FinanceEmail::where('unit_id', $unit->id)->first();
            
            if ($existingEmail) {
                $this->command->info("Finance email already exists for unit {$unit->unit}");
                continue;
            }

            // Create a primary finance email for each unit
            FinanceEmail::create([
                'unit_id' => $unit->id,
                'email' => "buyer.unit{$unit->unit}@gateeleven.test", // Test email format
                'recipient_name' => "Buyer - Unit {$unit->unit}",
                'type' => 'buyer',
                'is_primary' => true,
            ]);

            $this->command->info("Created finance email for unit {$unit->unit}");
        }

        $this->command->info('Finance emails seeding completed!');
    }
}
