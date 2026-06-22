<?php

namespace App\Modules\DamageCauseMaster\Database\Seeders;

use App\Modules\DamageCauseMaster\Models\DamageCauseMaster;
use Illuminate\Database\Seeder;

class DamageCauseMasterSeeder extends Seeder
{
    public function run(): void
    {
        // Damage cause categories for estimates / insurance claims.
        $real = [
            ['name' => 'ACCIDENT DAMAGE',    'code' => 'ACC'],
            ['name' => 'WEAR & TEAR',        'code' => 'WNT'],
            ['name' => 'ELECTRICAL FAILURE', 'code' => 'ELF'],
            ['name' => 'MECHANICAL FAILURE', 'code' => 'MEF'],
        ];

        foreach ($real as $type) {
            DamageCauseMaster::firstOrCreate(
                ['name' => $type['name']],
                ['code' => $type['code'], 'is_active' => true],
            );
        }
    }
}
