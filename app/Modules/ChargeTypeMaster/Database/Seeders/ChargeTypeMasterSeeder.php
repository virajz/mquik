<?php

namespace App\Modules\ChargeTypeMaster\Database\Seeders;

use App\Modules\ChargeTypeMaster\Models\ChargeTypeMaster;
use Illuminate\Database\Seeder;

class ChargeTypeMasterSeeder extends Seeder
{
    public function run(): void
    {
        // Real complaint categories used by the workshop CRM and advisor routing.
        $real = [
            ['name' => 'FREIGHT',          'code' => 'FRT'],
            ['name' => 'P & F',            'code' => 'PNF'],
            ['name' => 'COURIER',          'code' => 'CUR'],
            ['name' => 'LOADING',          'code' => 'LOD'],
            ['name' => 'INSURANCE',        'code' => 'INS'],
            ['name' => 'DEPOSIT',          'code' => 'DEP'],
            ['name' => 'OTHER',            'code' => 'OTH'],
        ];

        foreach ($real as $type) {
            ChargeTypeMaster::firstOrCreate(
                ['name' => $type['name']],
                ['code' => $type['code'], 'is_active' => true],
            );
        }
    }
}
