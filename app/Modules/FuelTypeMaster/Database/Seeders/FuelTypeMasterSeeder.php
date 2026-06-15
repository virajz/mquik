<?php

namespace App\Modules\FuelTypeMaster\Database\Seeders;

use App\Modules\FuelTypeMaster\Models\FuelTypeMaster;
use Illuminate\Database\Seeder;

class FuelTypeMasterSeeder extends Seeder
{
    public function run(): void
    {
        // Standard vehicle fuel types.
        $real = [
            ['name' => 'PETROL',   'code' => 'PET'],
            ['name' => 'DIESEL',   'code' => 'DSL'],
            ['name' => 'CNG',      'code' => 'CNG'],
            ['name' => 'ELECTRIC', 'code' => 'EV'],
            ['name' => 'HYBRID',   'code' => 'HYB'],
            ['name' => 'LPG',      'code' => 'LPG'],
        ];

        foreach ($real as $type) {
            FuelTypeMaster::firstOrCreate(
                ['name' => $type['name']],
                ['code' => $type['code'], 'is_active' => true],
            );
        }
    }
}
