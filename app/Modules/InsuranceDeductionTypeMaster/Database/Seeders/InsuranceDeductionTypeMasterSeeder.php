<?php

namespace App\Modules\InsuranceDeductionTypeMaster\Database\Seeders;

use App\Modules\InsuranceDeductionTypeMaster\Models\InsuranceDeductionTypeMaster;
use Illuminate\Database\Seeder;

class InsuranceDeductionTypeMasterSeeder extends Seeder
{
    public function run(): void
    {
        // Real complaint categories used by the workshop CRM and advisor routing.
        $real = [
            ['name' => 'CLAIM CHARGE',   'code' => 'CLM'],
            ['name' => 'SALVAGE CHARGE', 'code' => 'SLV'],
            ['name' => 'OTHER CHARGE',   'code' => 'OTH'],
        ];

        foreach ($real as $type) {
            InsuranceDeductionTypeMaster::firstOrCreate(
                ['name' => $type['name']],
                ['code' => $type['code'], 'is_active' => true],
            );
        }
    }
}
