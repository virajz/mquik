<?php

namespace App\Modules\ChargeTypeMaster\Database\Seeders;

use App\Modules\ChargeTypeMaster\Models\ChargeTypeMaster;
use Illuminate\Database\Seeder;

class ChargeTypeMasterSeeder extends Seeder
{
    public function run(): void
    {
        // Additional-charge heads used on purchases, RFQs and invoices.
        $real = [
            ['name' => 'FREIGHT',          'code' => 'FRT'],
            ['name' => 'P & F',            'code' => 'PNF'],
            ['name' => 'TRANSPORT',        'code' => 'TRP'],
            ['name' => 'PACKING',          'code' => 'PKG'],
            ['name' => 'HANDLING',         'code' => 'HDL'],
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
