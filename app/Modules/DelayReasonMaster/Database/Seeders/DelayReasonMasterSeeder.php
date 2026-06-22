<?php

namespace App\Modules\DelayReasonMaster\Database\Seeders;

use App\Modules\DelayReasonMaster\Models\DelayReasonMaster;
use Illuminate\Database\Seeder;

class DelayReasonMasterSeeder extends Seeder
{
    public function run(): void
    {
        // Reasons a work order is delayed.
        $real = [
            ['name' => 'TOOL NOT AVAILABLE',           'code' => 'TNA'],
            ['name' => 'ELECTRIC POWER NOT AVAILABLE', 'code' => 'PWR'],
            ['name' => 'ADDITIONAL DIAGNOSIS REQUIRED', 'code' => 'ADR'],
        ];

        foreach ($real as $type) {
            DelayReasonMaster::firstOrCreate(
                ['name' => $type['name']],
                ['code' => $type['code'], 'is_active' => true],
            );
        }
    }
}
