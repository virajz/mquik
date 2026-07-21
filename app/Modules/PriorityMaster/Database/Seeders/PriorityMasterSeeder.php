<?php

namespace App\Modules\PriorityMaster\Database\Seeders;

use App\Modules\PriorityMaster\Models\PriorityMaster;
use Illuminate\Database\Seeder;

class PriorityMasterSeeder extends Seeder
{
    public function run(): void
    {
        // One priority list for the whole workshop. sort_order encodes severity;
        // applies_to keeps parts-supply urgencies out of job dropdowns and vice versa.
        $real = [
            ['name' => 'NORMAL',    'code' => 'NRM', 'sort_order' => 10, 'applies_to' => PriorityMaster::APPLIES_BOTH],
            ['name' => 'HIGH',      'code' => 'HGH', 'sort_order' => 20, 'applies_to' => PriorityMaster::APPLIES_BOTH],
            ['name' => 'URGENT',    'code' => 'URG', 'sort_order' => 30, 'applies_to' => PriorityMaster::APPLIES_BOTH],
            ['name' => 'BREAKDOWN', 'code' => 'BRK', 'sort_order' => 40, 'applies_to' => PriorityMaster::APPLIES_PARTS],
            ['name' => 'CRITICAL',  'code' => 'CRT', 'sort_order' => 50, 'applies_to' => PriorityMaster::APPLIES_PARTS],
        ];

        foreach ($real as $row) {
            PriorityMaster::firstOrCreate(
                ['name' => $row['name']],
                [
                    'code' => $row['code'],
                    'sort_order' => $row['sort_order'],
                    'applies_to' => $row['applies_to'],
                    'is_active' => true,
                ],
            );
        }
    }
}
