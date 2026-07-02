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
            ['name' => 'DAMAGED',                'code' => 'DMG'],
            ['name' => 'DEFECTIVE',              'code' => 'DEF'],
            ['name' => 'DATE EXPIRED',           'code' => 'EXP'],
            ['name' => 'LEAKAGE',                'code' => 'LEK'],
            ['name' => 'QUALITY REJECTION',      'code' => 'QRJ'],
            ['name' => 'COLOUR MIXING LOSS',     'code' => 'CML'],
            ['name' => 'EVAPORATION LOSS',       'code' => 'EVP'],
            ['name' => 'SPILLAGE LOSS',          'code' => 'SPL'],
            ['name' => 'REWORK LOSS',            'code' => 'RWK'],
            ['name' => 'TRIAL OR TESTING LOSS',  'code' => 'TRL'],
            ['name' => 'STOCK ADJUSTMENT',       'code' => 'ADJ'],
            ['name' => 'THEFT OR PILFERAGE LOSS', 'code' => 'THF'],
            ['name' => 'WORKSHOP MAINTENANCE',   'code' => 'WMT'],
            ['name' => 'OTHER',                  'code' => 'OTH'],
        ];

        foreach ($real as $type) {
            LossReasonMaster::firstOrCreate(
                ['name' => $type['name']],
                ['code' => $type['code'], 'is_active' => true],
            );
        }
    }
}
