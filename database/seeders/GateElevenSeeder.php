<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Property;
use App\Models\Unit;

class GateElevenSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create Gate Eleven property
        $property = Property::create([
            'project_name' => 'Gate Eleven Residences',
            'location' => 'Dubai',
        ]);

        // Sample unit numbers for Gate Eleven (similar format to Viera Residences)
        $unitNumbers = [
            '0101', '0102', '0103', '0104', '0105',
            '0201', '0202', '0203', '0204', '0205',
            '0301', '0302', '0303', '0304', '0305',
            '0401', '0402', '0403', '0404', '0405',
            '0501', '0502', '0503', '0504', '0505',
            '1101', '1102', '1103', '1104', '1105',
            '1201', '1202', '1203', '1204', '1205',
            '1301', '1302', '1303', '1304', '1305',
            '1401', '1402', '1403', '1404', '1405',
            '1501', '1502', '1503', '1504', '1505',
        ];

        foreach ($unitNumbers as $unitNumber) {
            Unit::create([
                'property_id' => $property->id,
                'unit' => $unitNumber,
                'status' => 'unclaimed',
            ]);
        }
    }
}
