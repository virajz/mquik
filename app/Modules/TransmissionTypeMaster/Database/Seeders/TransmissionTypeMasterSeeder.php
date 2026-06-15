<?php

namespace App\Modules\TransmissionTypeMaster\Database\Seeders;

use App\Modules\TransmissionTypeMaster\Models\TransmissionTypeMaster;
use Illuminate\Database\Seeder;

class TransmissionTypeMasterSeeder extends Seeder
{
    public function run(): void
    {
        // Standard vehicle transmission types.
        $real = [
            ['name' => 'MANUAL',    'code' => 'MT'],
            ['name' => 'AUTOMATIC', 'code' => 'AT'],
            ['name' => 'AMT',       'code' => 'AMT'],
            ['name' => 'CVT',       'code' => 'CVT'],
            ['name' => 'DCT',       'code' => 'DCT'],
            ['name' => 'IMT',       'code' => 'IMT'],
        ];

        foreach ($real as $type) {
            TransmissionTypeMaster::firstOrCreate(
                ['name' => $type['name']],
                ['code' => $type['code'], 'is_active' => true],
            );
        }
    }
}
