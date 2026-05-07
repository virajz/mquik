<?php

namespace App\Modules\GstTypeMaster\Database\Seeders;

use App\Modules\GstTypeMaster\Models\GstTypeMaster;
use Illuminate\Database\Seeder;

class GstTypeMasterSeeder extends Seeder
{
    public function run(): void
    {
        $real = [
            ['name' => 'COMPOSITION',  'code' => 'COMP'],
            ['name' => 'REGULAR',      'code' => 'REG'],
            ['name' => 'UNREGISTERED', 'code' => 'UNREG'],
            ['name' => 'SEZ',          'code' => 'SEZ'],
            ['name' => 'EXPORT',       'code' => 'EXP'],
            ['name' => 'OVERSEAS',     'code' => 'OVR'],
        ];

        foreach ($real as $row) {
            GstTypeMaster::firstOrCreate(
                ['name' => $row['name']],
                ['code' => $row['code'], 'is_active' => true],
            );
        }
    }
}
