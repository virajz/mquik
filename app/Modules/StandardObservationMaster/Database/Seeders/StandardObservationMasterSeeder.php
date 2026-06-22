<?php

namespace App\Modules\StandardObservationMaster\Database\Seeders;

use App\Modules\StandardObservationMaster\Models\StandardObservationMaster;
use Illuminate\Database\Seeder;

class StandardObservationMasterSeeder extends Seeder
{
    public function run(): void
    {
        // Reusable inspection observations for faster checklist entry.
        $real = [
            ['name' => 'BRAKE PADS WORN OUT',   'code' => 'BPW'],
            ['name' => 'OIL LEAKAGE',           'code' => 'OIL'],
            ['name' => 'MINOR SCRATCH',         'code' => 'SCR'],
            ['name' => 'COOLANT LOW',           'code' => 'CLT'],
            ['name' => 'TYRE WEAR UNEVEN',      'code' => 'TYR'],
            ['name' => 'BATTERY WEAK',          'code' => 'BAT'],
            ['name' => 'WIPER BLADES WORN',     'code' => 'WPR'],
            ['name' => 'AC COOLING LOW',        'code' => 'AC'],
            ['name' => 'SUSPENSION NOISE',      'code' => 'SUS'],
            ['name' => 'HEADLIGHT DIM',         'code' => 'HLT'],
        ];

        foreach ($real as $type) {
            StandardObservationMaster::firstOrCreate(
                ['name' => $type['name']],
                ['code' => $type['code'], 'is_active' => true],
            );
        }
    }
}
