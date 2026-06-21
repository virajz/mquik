<?php

namespace App\Modules\ClaimTypeMaster\Database\Seeders;

use App\Modules\ClaimTypeMaster\Models\ClaimTypeMaster;
use Illuminate\Database\Seeder;

class ClaimTypeMasterSeeder extends Seeder
{
    public function run(): void
    {
        $real = [
            ['name' => 'CASHLESS',      'code' => 'CSH'],
            ['name' => 'REIMBURSEMENT', 'code' => 'RMB'],
            ['name' => 'ACCIDENT',      'code' => 'ACC'],
            ['name' => 'THEFT',         'code' => 'THF'],
            ['name' => 'TOTAL LOSS',    'code' => 'TL'],
        ];

        foreach ($real as $row) {
            ClaimTypeMaster::firstOrCreate(['name' => $row['name']], ['code' => $row['code'], 'is_active' => true]);
        }
    }
}
