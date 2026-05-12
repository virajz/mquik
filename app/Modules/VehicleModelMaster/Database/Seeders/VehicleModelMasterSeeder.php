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
        // Real Indian-market vehicles by brand. Fuel type now lives at the
        // variant level, not the model level.
        $catalogue = [
            'MARUTI SUZUKI' => [
                ['SWIFT', 'hatchback'],
                ['DZIRE', 'sedan'],
                ['BREZZA', 'suv'],
                ['ERTIGA', 'muv'],
            ],
            'HYUNDAI' => [
                ['CRETA', 'suv'],
                ['VENUE', 'suv'],
                ['I20', 'hatchback'],
            ],
            'TATA' => [
                ['NEXON', 'suv'],
                ['PUNCH', 'suv'],
                ['HARRIER', 'suv'],
            ],
            'MAHINDRA' => [
                ['XUV700', 'suv'],
                ['SCORPIO N', 'suv'],
                ['THAR', 'suv'],
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
            foreach ($models as [$name, $segment]) {
                VehicleModelMaster::firstOrCreate(
                    ['brand_id' => $brand->id, 'name' => $name],
                    ['vehicle_segment_id' => $segmentId($segment), 'is_active' => true],
                );
            }
        }
    }
}
