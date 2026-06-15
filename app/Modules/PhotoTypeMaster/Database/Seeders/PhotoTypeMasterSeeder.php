<?php

namespace App\Modules\PhotoTypeMaster\Database\Seeders;

use App\Modules\PhotoTypeMaster\Models\PhotoTypeMaster;
use Illuminate\Database\Seeder;

class PhotoTypeMasterSeeder extends Seeder
{
    public function run(): void
    {
        // Insurance-style capture slots, grouped into tabs. The sort_order encodes
        // both the tab order (the hundreds digit) and the slot order within a tab.
        $groups = [
            'EXTERIOR' => [
                ['name' => 'FRONT', 'code' => 'EXT-F'],
                ['name' => 'FRONT RIGHT CORNER', 'code' => 'EXT-FR'],
                ['name' => 'RIGHT SIDE', 'code' => 'EXT-R'],
                ['name' => 'REAR RIGHT CORNER', 'code' => 'EXT-RR'],
                ['name' => 'REAR', 'code' => 'EXT-B'],
                ['name' => 'REAR LEFT CORNER', 'code' => 'EXT-RL'],
                ['name' => 'LEFT SIDE', 'code' => 'EXT-L'],
                ['name' => 'FRONT LEFT CORNER', 'code' => 'EXT-FL'],
                ['name' => 'ROOF', 'code' => 'EXT-RF'],
                ['name' => 'FULL VEHICLE', 'code' => 'EXT-FV'],
            ],
            'INTERIOR' => [
                ['name' => 'DASHBOARD', 'code' => 'INT-DSH'],
                ['name' => 'FRONT SEATS', 'code' => 'INT-FS'],
                ['name' => 'REAR SEATS', 'code' => 'INT-RS'],
                ['name' => 'BOOT / TRUNK', 'code' => 'INT-BT'],
            ],
            'METER' => [
                ['name' => 'ODOMETER', 'code' => 'MTR-ODO'],
                ['name' => 'FUEL GAUGE', 'code' => 'MTR-FUEL'],
            ],
            'ENGINE' => [
                ['name' => 'ENGINE BAY', 'code' => 'ENG-BAY'],
                ['name' => 'UNDER BODY', 'code' => 'ENG-UND'],
            ],
            'WHEELS & TYRES' => [
                ['name' => 'FRONT LEFT TYRE', 'code' => 'WHL-FL'],
                ['name' => 'FRONT RIGHT TYRE', 'code' => 'WHL-FR'],
                ['name' => 'REAR LEFT TYRE', 'code' => 'WHL-RL'],
                ['name' => 'REAR RIGHT TYRE', 'code' => 'WHL-RR'],
                ['name' => 'SPARE TYRE', 'code' => 'WHL-SP'],
            ],
            'DOCUMENTS' => [
                ['name' => 'VIN PLATE', 'code' => 'DOC-VIN'],
                ['name' => 'NUMBER PLATE', 'code' => 'DOC-NUM'],
                ['name' => 'RC BOOK', 'code' => 'DOC-RC'],
                ['name' => 'INSURANCE POLICY', 'code' => 'DOC-INS'],
            ],
        ];

        $groupBase = 100;
        foreach ($groups as $group => $slots) {
            $slotOrder = 1;
            foreach ($slots as $slot) {
                PhotoTypeMaster::updateOrCreate(
                    ['name' => $slot['name']],
                    [
                        'code' => $slot['code'],
                        'group' => $group,
                        'sort_order' => $groupBase + $slotOrder,
                        'is_active' => true,
                    ],
                );
                $slotOrder++;
            }
            $groupBase += 100;
        }
    }
}
