<?php

namespace App\Modules\VehicleModelMaster\Database\Seeders;

use App\Modules\VehicleBrandMaster\Models\VehicleBrandMaster;
use App\Modules\VehicleModelMaster\Models\VehicleModelMaster;
use App\Modules\VehicleSegmentMaster\Models\VehicleSegmentMaster;
use Illuminate\Database\Seeder;

class VehicleModelMasterSeeder extends Seeder
{
    public function run(): void
    {
        // Real Indian-market vehicles by brand
        $catalogue = [
            'MARUTI SUZUKI' => [
                ['SWIFT', 'hatchback', 'petrol'],
                ['DZIRE', 'sedan', 'petrol'],
                ['BREZZA', 'suv', 'petrol'],
                ['ERTIGA', 'muv', 'petrol'],
            ],
            'HYUNDAI' => [
                ['CRETA', 'suv', 'petrol'],
                ['VENUE', 'suv', 'petrol'],
                ['I20', 'hatchback', 'petrol'],
            ],
            'TATA' => [
                ['NEXON', 'suv', 'petrol'],
                ['PUNCH', 'suv', 'petrol'],
                ['HARRIER', 'suv', 'diesel'],
            ],
            'MAHINDRA' => [
                ['XUV700', 'suv', 'diesel'],
                ['SCORPIO N', 'suv', 'diesel'],
                ['THAR', 'suv', 'diesel'],
            ],
        ];

        $segmentId = fn (string $name) => VehicleSegmentMaster::firstOrCreate(
            ['name' => strtoupper($name)],
            ['is_active' => true],
        )->id;

        foreach ($catalogue as $brandName => $models) {
            $brand = VehicleBrandMaster::where('name', $brandName)->first();
            if (! $brand) {
                continue;
            }
            foreach ($models as [$name, $segment, $fuel]) {
                VehicleModelMaster::firstOrCreate(
                    ['brand_id' => $brand->id, 'name' => $name],
                    ['vehicle_segment_id' => $segmentId($segment), 'fuel_type' => $fuel, 'is_active' => true],
                );
            }
        }
    }
}
