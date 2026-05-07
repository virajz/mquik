<?php

namespace App\Modules\CourierCompanyMaster\Database\Seeders;

use App\Modules\CourierCompanyMaster\Models\CourierCompanyMaster;
use Illuminate\Database\Seeder;

class CourierCompanyMasterSeeder extends Seeder
{
    public function run(): void
    {
        $real = [
            ['name' => 'DTDC',                  'code' => 'DTDC'],
            ['name' => 'BLUEDART',              'code' => 'BD'],
            ['name' => 'DELHIVERY',             'code' => 'DEL'],
            ['name' => 'PROFESSIONAL COURIERS', 'code' => 'PRO'],
            ['name' => 'FEDEX',                 'code' => 'FDX'],
            ['name' => 'INDIA POST',            'code' => 'IPOST'],
            ['name' => 'GATI',                  'code' => 'GATI'],
            ['name' => 'ARAMEX',                'code' => 'ARMX'],
        ];

        foreach ($real as $row) {
            CourierCompanyMaster::firstOrCreate(
                ['name' => $row['name']],
                ['code' => $row['code'], 'is_active' => true],
            );
        }
    }
}
