<?php

namespace App\Modules\RegistrationTypeMaster\Database\Seeders;

use App\Modules\RegistrationTypeMaster\Models\RegistrationTypeMaster;
use Illuminate\Database\Seeder;

class RegistrationTypeMasterSeeder extends Seeder
{
    public function run(): void
    {
        // Vehicle registration / plate types (mirrors the legacy plate-type enum).
        $real = [
            ['name' => 'PRIVATE',    'code' => 'PVT'],
            ['name' => 'COMMERCIAL', 'code' => 'COM'],
            ['name' => 'GOVERNMENT', 'code' => 'GOV'],
            ['name' => 'BH SERIES',  'code' => 'BH'],
            ['name' => 'MILITARY',   'code' => 'MIL'],
            ['name' => 'OTHER',      'code' => 'OTH'],
        ];

        foreach ($real as $type) {
            RegistrationTypeMaster::firstOrCreate(
                ['name' => $type['name']],
                ['code' => $type['code'], 'is_active' => true],
            );
        }
    }
}
