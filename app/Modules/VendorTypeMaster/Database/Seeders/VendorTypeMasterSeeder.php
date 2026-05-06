<?php

namespace App\Modules\VendorTypeMaster\Database\Seeders;

use App\Modules\VendorTypeMaster\Models\VendorTypeMaster;
use Illuminate\Database\Seeder;

class VendorTypeMasterSeeder extends Seeder
{
    public function run(): void
    {
        // Real workshop vendor types — these line up with how purchase orders
        // and bill entries are routed (parts vs labour vs services vs insurance).
        $real = [
            ['name' => 'SPARE PARTS',          'code' => 'SP'],
            ['name' => 'OSL (OUTSIDE LABOUR)', 'code' => 'OSL'],
            ['name' => 'OEM',                  'code' => 'OEM'],
            ['name' => 'SERVICE',              'code' => 'SVC'],
            ['name' => 'INSURANCE',            'code' => 'INS'],
            ['name' => 'OTHER',                'code' => 'OTH'],
        ];

        foreach ($real as $type) {
            VendorTypeMaster::firstOrCreate(
                ['name' => $type['name']],
                ['code' => $type['code'], 'is_active' => true],
            );
        }
    }
}
