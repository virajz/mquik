<?php

namespace App\Modules\TransportModeMaster\Database\Seeders;

use App\Modules\TransportModeMaster\Models\TransportModeMaster;
use Illuminate\Database\Seeder;

class TransportModeMasterSeeder extends Seeder
{
    public function run(): void
    {
        // Real complaint categories used by the workshop CRM and advisor routing.
        $real = [
            ['name' => 'SELF',            'code' => 'SLF'],
            ['name' => 'WORKSHOP DRIVER', 'code' => 'WDR'],
            ['name' => 'COURIER',         'code' => 'CUR'],
            ['name' => 'PORTER',          'code' => 'PTR'],
            ['name' => 'LIFTOR',          'code' => 'LFT'],
        ];

        foreach ($real as $type) {
            TransportModeMaster::firstOrCreate(
                ['name' => $type['name']],
                ['code' => $type['code'], 'is_active' => true],
            );
        }
    }
}
