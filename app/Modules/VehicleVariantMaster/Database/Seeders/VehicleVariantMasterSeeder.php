<?php

namespace App\Modules\VehicleVariantMaster\Database\Seeders;

use App\Modules\VehicleModelMaster\Models\VehicleModelMaster;
use App\Modules\VehicleVariantMaster\Models\VehicleVariantMaster;
use Illuminate\Database\Seeder;

class VehicleVariantMasterSeeder extends Seeder
{
    public function run(): void
    {
        $catalogue = [
            'SWIFT' => [
                ['LXi', 'manual', '1197cc'],
                ['VXi', 'manual', '1197cc'],
                ['VXi AMT', 'amt', '1197cc'],
                ['ZXi+', 'manual', '1197cc'],
            ],
            'CRETA' => [
                ['E', 'manual', '1493cc'],
                ['EX', 'manual', '1493cc'],
                ['SX', 'automatic', '1493cc'],
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
                    ['transmission' => $tx, 'engine_cc' => $cc, 'is_active' => true],
                );
            }
        }
    }
}
