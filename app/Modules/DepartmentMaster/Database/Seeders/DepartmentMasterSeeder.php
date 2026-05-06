<?php

namespace App\Modules\DepartmentMaster\Database\Seeders;

use App\Modules\DepartmentMaster\Models\DepartmentMaster;
use Illuminate\Database\Seeder;

class DepartmentMasterSeeder extends Seeder
{
    public function run(): void
    {
        // HR-side departments — what an employee is "in"
        $real = [
            ['name' => 'SERVICE',     'code' => 'SVC'],
            ['name' => 'BODYSHOP',    'code' => 'BSH'],
            ['name' => 'TYRE',        'code' => 'TYR'],
            ['name' => 'ACCESSORIES', 'code' => 'ACC'],
            ['name' => 'STORES',      'code' => 'STR'],
            ['name' => 'ACCOUNTS',    'code' => 'ACT'],
            ['name' => 'HR',          'code' => 'HR'],
            ['name' => 'ADMIN',       'code' => 'ADM'],
            ['name' => 'RECEPTION',   'code' => 'RCP'],
            ['name' => 'CRM',         'code' => 'CRM'],
        ];

        foreach ($real as $row) {
            DepartmentMaster::firstOrCreate(
                ['name' => $row['name']],
                $row + ['is_active' => true],
            );
        }
    }
}
