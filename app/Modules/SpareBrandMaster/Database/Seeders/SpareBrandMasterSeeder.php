<?php

namespace App\Modules\SpareBrandMaster\Database\Seeders;

use App\Modules\SpareBrandMaster\Models\SpareBrandMaster;
use Illuminate\Database\Seeder;

class SpareBrandMasterSeeder extends Seeder
{
    public function run(): void
    {
        // Real workshop brands first — these match what the team will type into IPI/VPI
        $real = [
            ['name' => 'BOSCH',  'code' => 'BSH'],
            ['name' => 'DENSO',  'code' => 'DNS'],
            ['name' => 'NGK',    'code' => 'NGK'],
            ['name' => 'MRF',    'code' => 'MRF'],
            ['name' => 'APOLLO', 'code' => 'APL'],
        ];

        foreach ($real as $brand) {
            SpareBrandMaster::firstOrCreate(
                ['name' => $brand['name']],
                ['code' => $brand['code'], 'is_active' => true],
            );
        }

        // Plus a handful of fake ones for testing pagination, search, etc.
        SpareBrandMaster::factory()->count(10)->create();
    }
}
