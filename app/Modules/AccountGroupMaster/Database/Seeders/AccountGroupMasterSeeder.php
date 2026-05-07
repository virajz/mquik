<?php

namespace App\Modules\AccountGroupMaster\Database\Seeders;

use App\Modules\AccountGroupMaster\Models\AccountGroupMaster;
use Illuminate\Database\Seeder;

class AccountGroupMasterSeeder extends Seeder
{
    public function run(): void
    {
        $real = [
            ['name' => 'INCOME',    'code' => 'INC'],
            ['name' => 'EXPENSE',   'code' => 'EXP'],
            ['name' => 'ASSET',     'code' => 'AST'],
            ['name' => 'LIABILITY', 'code' => 'LIA'],
            ['name' => 'EQUITY',    'code' => 'EQT'],
        ];

        foreach ($real as $row) {
            AccountGroupMaster::firstOrCreate(
                ['name' => $row['name']],
                ['code' => $row['code'], 'is_active' => true],
            );
        }
    }
}
