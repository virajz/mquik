<?php

namespace App\Modules\IpoCancellationReasonMaster\Database\Seeders;

use App\Modules\IpoCancellationReasonMaster\Models\IpoCancellationReasonMaster;
use Illuminate\Database\Seeder;

class IpoCancellationReasonMasterSeeder extends Seeder
{
    public function run(): void
    {
        // Real complaint categories used by the workshop CRM and advisor routing.
        $real = [
            ['name' => 'PARTS NOT AVAILABLE', 'code' => 'PNA'],
            ['name' => 'WRONG FITMENT',      'code' => 'WFT'],
            ['name' => 'NO RESOLUTION',      'code' => 'NRS'],
        ];

        foreach ($real as $type) {
            IpoCancellationReasonMaster::firstOrCreate(
                ['name' => $type['name']],
                ['code' => $type['code'], 'is_active' => true],
            );
        }
    }
}
