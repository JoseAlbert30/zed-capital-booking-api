<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Property;

class PropertySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create Viera Residences property
        Property::updateOrCreate(
            ['project_name' => 'Viera Residences'],
            [
                'project_name' => 'Viera Residences',
                'location' => 'Dubai Production City, Dubai',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        $this->command->info('Viera Residences property created successfully!');
    }
}
