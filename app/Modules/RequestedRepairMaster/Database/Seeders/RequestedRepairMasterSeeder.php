<?php

namespace App\Modules\RequestedRepairMaster\Database\Seeders;

use App\Modules\RequestedRepairMaster\Models\RequestedRepairMaster;
use Illuminate\Database\Seeder;

class RequestedRepairMasterSeeder extends Seeder
{
    public function run(): void
    {
        // Common miscellaneous repairs a customer can request at intake.
        $real = [
            ['name' => 'WHEEL ALIGNMENT',     'code' => 'ALN'],
            ['name' => 'WHEEL BALANCING',     'code' => 'BAL'],
            ['name' => 'AC GAS REFILL',       'code' => 'ACG'],
            ['name' => 'WIPER REPLACEMENT',   'code' => 'WIP'],
            ['name' => 'BATTERY CHECK-UP',    'code' => 'BAT'],
            ['name' => 'HEADLAMP RESTORATION', 'code' => 'HDL'],
            ['name' => 'UNDERBODY COATING',   'code' => 'UBC'],
            ['name' => 'TEFLON COATING',      'code' => 'TEF'],
        ];

        foreach ($real as $type) {
            RequestedRepairMaster::firstOrCreate(
                ['name' => $type['name']],
                ['code' => $type['code'], 'is_active' => true],
            );
        }
    }
}
