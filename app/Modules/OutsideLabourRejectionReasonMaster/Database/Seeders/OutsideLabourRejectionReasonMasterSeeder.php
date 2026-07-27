<?php

namespace App\Modules\OutsideLabourRejectionReasonMaster\Database\Seeders;

use App\Modules\OutsideLabourRejectionReasonMaster\Models\OutsideLabourRejectionReasonMaster;
use Illuminate\Database\Seeder;

class OutsideLabourRejectionReasonMasterSeeder extends Seeder
{
    public function run(): void
    {
        // Why a contractor/vendor rejects an outside-labour inquiry.
        $real = [
            ['name' => 'HIGH COST',                    'code' => 'HC'],
            ['name' => 'DELAY IN DELIVERY',            'code' => 'DEL'],
            ['name' => 'POOR QUALITY HISTORY',         'code' => 'PQH'],
            ['name' => 'SERVICE PROVIDER NOT AVAILABLE', 'code' => 'NA'],
            ['name' => 'CAPACITY FULL',                'code' => 'CAP'],
            ['name' => 'OUT OF SCOPE',                 'code' => 'OOS'],
        ];

        foreach ($real as $type) {
            OutsideLabourRejectionReasonMaster::firstOrCreate(
                ['name' => $type['name']],
                ['code' => $type['code'], 'is_active' => true],
            );
        }
    }
}
