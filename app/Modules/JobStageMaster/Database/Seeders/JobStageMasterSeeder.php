<?php

namespace App\Modules\JobStageMaster\Database\Seeders;

use App\Modules\JobStageMaster\Models\JobStageMaster;
use Illuminate\Database\Seeder;

class JobStageMasterSeeder extends Seeder
{
    public function run(): void
    {
        // The job lifecycle, split by track. sort_order encodes track base (100s
        // regular, 200s insurance) + the position within the track.
        $tracks = [
            'regular' => [
                ['name' => 'ESTIMATE APPROVAL', 'code' => 'REG-EST'],
                ['name' => 'REPAIR',            'code' => 'REG-REP'],
                ['name' => 'BILLING',           'code' => 'REG-BIL'],
                ['name' => 'DELIVERY',          'code' => 'REG-DLV'],
            ],
            'insurance' => [
                ['name' => 'DOCUMENT COLLECTION',         'code' => 'INS-DOC'],
                ['name' => 'CLAIM INTIMATION',            'code' => 'INS-CLM'],
                ['name' => 'SURVEYOR INSPECTION',         'code' => 'INS-SRV'],
                ['name' => 'ESTIMATE APPROVAL (INS)',     'code' => 'INS-EST'],
                ['name' => 'REPAIR (INS)',                'code' => 'INS-REP'],
                ['name' => 'BILLING (INS)',               'code' => 'INS-BIL'],
                ['name' => 'CLAIM SETTLEMENT & DELIVERY', 'code' => 'INS-SET'],
            ],
        ];

        $base = 100;
        foreach ($tracks as $track => $stages) {
            $order = 1;
            foreach ($stages as $stage) {
                JobStageMaster::updateOrCreate(
                    ['name' => $stage['name']],
                    ['code' => $stage['code'], 'track' => $track, 'sort_order' => $base + $order, 'is_active' => true],
                );
                $order++;
            }
            $base += 100;
        }
    }
}
