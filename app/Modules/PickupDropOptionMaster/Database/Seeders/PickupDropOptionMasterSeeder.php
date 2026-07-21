<?php

namespace App\Modules\PickupDropOptionMaster\Database\Seeders;

use App\Modules\PickupDropOptionMaster\Models\PickupDropOptionMaster;
use Illuminate\Database\Seeder;

class PickupDropOptionMasterSeeder extends Seeder
{
    public function run(): void
    {
        // How the vehicle reaches and leaves the workshop. involves_pickup /
        // involves_drop drive whether the appointment raises a Pickup/Drop job.
        $real = [
            ['name' => 'CUSTOMER SELF DROP',          'code' => 'SELF', 'pickup' => false, 'drop' => false],
            ['name' => 'WORKSHOP PICKUP ONLY',        'code' => 'WPU',  'pickup' => true,  'drop' => false],
            ['name' => 'WORKSHOP DROP ONLY',          'code' => 'WDR',  'pickup' => false, 'drop' => true],
            ['name' => 'WORKSHOP PICKUP & DROP BOTH', 'code' => 'WPD',  'pickup' => true,  'drop' => true],
            ['name' => 'CUSTOMER SELF TOWING',        'code' => 'STOW', 'pickup' => false, 'drop' => false],
            ['name' => 'WORKSHOP TOWING',             'code' => 'WTOW', 'pickup' => true,  'drop' => false],
            ['name' => 'DOORSTEP INSPECTION',         'code' => 'DOOR', 'pickup' => true,  'drop' => false],
        ];

        foreach ($real as $row) {
            PickupDropOptionMaster::firstOrCreate(
                ['name' => $row['name']],
                [
                    'code' => $row['code'],
                    'involves_pickup' => $row['pickup'],
                    'involves_drop' => $row['drop'],
                    'is_active' => true,
                ],
            );
        }
    }
}
