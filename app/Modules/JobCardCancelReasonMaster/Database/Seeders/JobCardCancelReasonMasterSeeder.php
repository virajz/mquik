<?php

namespace App\Modules\JobCardCancelReasonMaster\Database\Seeders;

use App\Modules\JobCardCancelReasonMaster\Models\JobCardCancelReasonMaster;
use Illuminate\Database\Seeder;

class JobCardCancelReasonMasterSeeder extends Seeder
{
    public function run(): void
    {
        // Common reasons a job card gets cancelled in a workshop.
        $real = [
            ['name' => 'CUSTOMER BACKED OUT',    'code' => null],
            ['name' => 'ESTIMATE TOO HIGH',      'code' => null],
            ['name' => 'VEHICLE NOT AVAILABLE',  'code' => null],
            ['name' => 'INSURANCE REJECTED',     'code' => null],
            ['name' => 'CUSTOMER OPTED OUT',     'code' => null],
            ['name' => 'DUPLICATE ENTRY',        'code' => null],
            ['name' => 'WRONG VEHICLE',          'code' => null],
            ['name' => 'NO PARTS AVAILABLE',     'code' => null],
        ];

        foreach ($real as $row) {
            JobCardCancelReasonMaster::firstOrCreate(
                ['name' => $row['name']],
                $row + ['is_active' => true],
            );
        }
    }
}
