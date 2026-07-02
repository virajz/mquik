<?php

namespace App\Modules\PerformanceSlabMaster\Database\Seeders;

use App\Modules\PerformanceSlabMaster\Models\PerformanceSlabMaster;
use Illuminate\Database\Seeder;

class PerformanceSlabMasterSeeder extends Seeder
{
    public function run(): void
    {
        $real = [
            ['name' => 'SLAB 1', 'code' => 'S1', 'min_percent' => 80,  'max_percent' => 89.99, 'incentive_amount' => 100],
            ['name' => 'SLAB 2', 'code' => 'S2', 'min_percent' => 90,  'max_percent' => 99.99, 'incentive_amount' => 200],
            ['name' => 'SLAB 3', 'code' => 'S3', 'min_percent' => 100, 'max_percent' => null,   'incentive_amount' => 300],
        ];

        foreach ($real as $slab) {
            PerformanceSlabMaster::firstOrCreate(
                ['name' => $slab['name']],
                [
                    'code' => $slab['code'],
                    'min_percent' => $slab['min_percent'],
                    'max_percent' => $slab['max_percent'],
                    'incentive_amount' => $slab['incentive_amount'],
                    'is_active' => true,
                ],
            );
        }
    }
}
