<?php

namespace App\Modules\EstimateRevisionReasonMaster\Database\Seeders;

use App\Modules\EstimateRevisionReasonMaster\Models\EstimateRevisionReasonMaster;
use Illuminate\Database\Seeder;

class EstimateRevisionReasonMasterSeeder extends Seeder
{
    public function run(): void
    {
        // Why a sales estimate was revised.
        $real = [
            ['name' => 'ADDITIONAL DAMAGE FOUND', 'code' => 'ADF'],
            ['name' => 'CUSTOMER REQUEST',        'code' => 'CRQ'],
            ['name' => 'INSURANCE QUERY',         'code' => 'INQ'],
            ['name' => 'PARTS PRICE CHANGE',      'code' => 'PPC'],
            ['name' => 'PRICE REVISION',          'code' => 'PRV'],
            ['name' => 'BILLING NAME REVISION',   'code' => 'BNR'],
            ['name' => 'SCOPE CHANGE',            'code' => 'SCH'],
        ];

        foreach ($real as $type) {
            EstimateRevisionReasonMaster::firstOrCreate(
                ['name' => $type['name']],
                ['code' => $type['code'], 'is_active' => true],
            );
        }
    }
}
