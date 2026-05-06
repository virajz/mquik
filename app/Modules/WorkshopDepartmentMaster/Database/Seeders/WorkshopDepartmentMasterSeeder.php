<?php

namespace App\Modules\WorkshopDepartmentMaster\Database\Seeders;

use App\Modules\WorkshopDepartmentMaster\Models\WorkshopDepartmentMaster;
use Illuminate\Database\Seeder;

class WorkshopDepartmentMasterSeeder extends Seeder
{
    public function run(): void
    {
        $real = [
            ['name' => 'SERVICE',     'code' => 'SVC'],
            ['name' => 'BODYSHOP',    'code' => 'BSH'],
            ['name' => 'TYRE',        'code' => 'TYR'],
            ['name' => 'ACCESSORIES', 'code' => 'ACC'],
            ['name' => 'DETAILING',   'code' => 'DET'],
            ['name' => 'INSURANCE',   'code' => 'INS'],
        ];

        foreach ($real as $row) {
            WorkshopDepartmentMaster::firstOrCreate(
                ['name' => $row['name']],
                $row + ['is_active' => true],
            );
        }
    }
}
