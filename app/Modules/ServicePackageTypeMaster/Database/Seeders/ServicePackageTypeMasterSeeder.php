<?php

namespace App\Modules\ServicePackageTypeMaster\Database\Seeders;

use App\Modules\ServicePackageTypeMaster\Models\ServicePackageTypeMaster;
use Illuminate\Database\Seeder;

class ServicePackageTypeMasterSeeder extends Seeder
{
    public function run(): void
    {
        // Service package categories.
        $real = [
            ['name' => 'PERIODIC SERVICE', 'code' => 'PMS'],
            ['name' => 'ACCIDENT REPAIR',  'code' => 'ACC'],
            ['name' => 'COMBO OFFER',      'code' => 'CMB'],
            ['name' => 'AMC',              'code' => 'AMC'],
        ];

        foreach ($real as $type) {
            ServicePackageTypeMaster::firstOrCreate(
                ['name' => $type['name']],
                ['code' => $type['code'], 'is_active' => true],
            );
        }
    }
}
