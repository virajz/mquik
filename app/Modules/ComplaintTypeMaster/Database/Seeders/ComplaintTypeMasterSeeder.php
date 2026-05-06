<?php

namespace App\Modules\ComplaintTypeMaster\Database\Seeders;

use App\Modules\ComplaintTypeMaster\Models\ComplaintTypeMaster;
use Illuminate\Database\Seeder;

class ComplaintTypeMasterSeeder extends Seeder
{
    public function run(): void
    {
        // Real complaint categories used by the workshop CRM and advisor routing.
        $real = [
            ['name' => 'ENGINE NOISE', 'code' => 'ENG'],
            ['name' => 'AC ISSUE',     'code' => 'AC'],
            ['name' => 'BRAKE',        'code' => 'BRK'],
            ['name' => 'ELECTRICAL',   'code' => 'ELE'],
            ['name' => 'BODY',         'code' => 'BDY'],
            ['name' => 'TYRE',         'code' => 'TYR'],
            ['name' => 'SUSPENSION',   'code' => 'SUS'],
            ['name' => 'OIL LEAK',     'code' => 'OIL'],
            ['name' => 'OVERHEATING',  'code' => 'OHT'],
            ['name' => 'GENERAL',      'code' => 'GEN'],
        ];

        foreach ($real as $type) {
            ComplaintTypeMaster::firstOrCreate(
                ['name' => $type['name']],
                ['code' => $type['code'], 'is_active' => true],
            );
        }
    }
}
