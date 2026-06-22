<?php

namespace App\Modules\ReturnTypeMaster\Database\Seeders;

use App\Modules\ReturnTypeMaster\Models\ReturnTypeMaster;
use Illuminate\Database\Seeder;

class ReturnTypeMasterSeeder extends Seeder
{
    public function run(): void
    {
        // Real complaint categories used by the workshop CRM and advisor routing.
        $real = [
            ['name' => 'DAMAGED RETURN',     'code' => 'DMG'],
            ['name' => 'WRONG ISSUE RETURN', 'code' => 'WIR'],
            ['name' => 'EXCESS RETURN',      'code' => 'EXR'],
        ];

        foreach ($real as $type) {
            ReturnTypeMaster::firstOrCreate(
                ['name' => $type['name']],
                ['code' => $type['code'], 'is_active' => true],
            );
        }
    }
}
