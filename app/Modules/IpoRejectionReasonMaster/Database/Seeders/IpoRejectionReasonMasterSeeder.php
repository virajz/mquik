<?php

namespace App\Modules\IpoRejectionReasonMaster\Database\Seeders;

use App\Modules\IpoRejectionReasonMaster\Models\IpoRejectionReasonMaster;
use Illuminate\Database\Seeder;

class IpoRejectionReasonMasterSeeder extends Seeder
{
    public function run(): void
    {
        // Real complaint categories used by the workshop CRM and advisor routing.
        $real = [
            ['name' => 'WRONG PART',       'code' => 'WRP'],
            ['name' => 'APPROVAL DENIED',  'code' => 'APD'],
            ['name' => 'DUPLICATE REQUEST', 'code' => 'DUP'],
        ];

        foreach ($real as $type) {
            IpoRejectionReasonMaster::firstOrCreate(
                ['name' => $type['name']],
                ['code' => $type['code'], 'is_active' => true],
            );
        }
    }
}
