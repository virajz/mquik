<?php

namespace App\Modules\LossReasonMaster\Database\Seeders;

use App\Modules\LossReasonMaster\Models\LossReasonMaster;
use Illuminate\Database\Seeder;

class LossReasonMasterSeeder extends Seeder
{
    public function run(): void
    {
        // Real complaint categories used by the workshop CRM and advisor routing.
        $real = [
            ['name' => 'DAMAGED',   'code' => 'DMG'],
            ['name' => 'DEFECTIVE', 'code' => 'DEF'],
        ];

        foreach ($real as $type) {
            LossReasonMaster::firstOrCreate(
                ['name' => $type['name']],
                ['code' => $type['code'], 'is_active' => true],
            );
        }
    }
}
