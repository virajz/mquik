<?php

namespace App\Modules\ChallanReasonMaster\Database\Seeders;

use App\Modules\ChallanReasonMaster\Models\ChallanReasonMaster;
use Illuminate\Database\Seeder;

class ChallanReasonMasterSeeder extends Seeder
{
    public function run(): void
    {
        // Real complaint categories used by the workshop CRM and advisor routing.
        $real = [
            ['name' => 'WARRANTY',             'code' => 'WAR'],
            ['name' => 'DAMAGE & ADJUSTMENT',  'code' => 'DMA'],
        ];

        foreach ($real as $type) {
            ChallanReasonMaster::firstOrCreate(
                ['name' => $type['name']],
                ['code' => $type['code'], 'is_active' => true],
            );
        }
    }
}
