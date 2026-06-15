<?php

namespace App\Modules\VehicleVariantMaster\Database\Seeders;

use App\Modules\FuelTypeMaster\Models\FuelTypeMaster;
use App\Modules\TransmissionTypeMaster\Models\TransmissionTypeMaster;
use App\Modules\VehicleModelMaster\Models\VehicleModelMaster;
use App\Modules\VehicleVariantMaster\Models\VehicleVariantMaster;
use Illuminate\Database\Seeder;

class VehicleVariantMasterSeeder extends Seeder
{
    public function run(): void
    {
        $transmissions = TransmissionTypeMaster::pluck('id', 'name');   // name => id
        $petrolId = FuelTypeMaster::where('name', 'PETROL')->value('id');

        $catalogue = [
            'SWIFT' => [
                ['LXi', 'MANUAL', '1197cc'],
                ['VXi', 'MANUAL', '1197cc'],
                ['VXi AMT', 'AMT', '1197cc'],
                ['ZXi+', 'MANUAL', '1197cc'],
            ],
            'CRETA' => [
                ['E', 'MANUAL', '1493cc'],
                ['EX', 'MANUAL', '1493cc'],
                ['SX', 'AUTOMATIC', '1493cc'],
            ],
        ];

        foreach ($catalogue as $modelName => $variants) {
            $model = VehicleModelMaster::where('name', $modelName)->first();
            if (! $model) {
                continue;
            }
            foreach ($variants as [$name, $tx, $cc]) {
                VehicleVariantMaster::firstOrCreate(
                    ['model_id' => $model->id, 'name' => $name],
                    [
                        'transmission_type_id' => $transmissions[$tx] ?? null,
                        'fuel_type_id' => $petrolId,
                        'engine_cc' => $cc,
                        'is_active' => true,
                    ],
                );
            }
        }
    }
}
