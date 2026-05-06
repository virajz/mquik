<?php

namespace App\Modules\ServiceTypeMaster\Database\Seeders;

use App\Modules\ServiceTypeMaster\Models\ServiceTypeMaster;
use App\Modules\WorkshopDepartmentMaster\Models\WorkshopDepartmentMaster;
use Illuminate\Database\Seeder;

class ServiceTypeMasterSeeder extends Seeder
{
    public function run(): void
    {
        $resolve = fn (string $name) => WorkshopDepartmentMaster::firstOrCreate(
            ['name' => strtoupper($name)],
            ['is_active' => true],
        )->id;

        $real = [
            ['name' => 'PERIODIC MAINTENANCE',  'code' => 'PMS',  'dept' => 'Service'],
            ['name' => 'GENERAL REPAIR',        'code' => 'GR',   'dept' => 'Service'],
            ['name' => 'RUNNING REPAIR',        'code' => 'RR',   'dept' => 'Service'],
            ['name' => 'FREE SERVICE',          'code' => 'FS',   'dept' => 'Service'],
            ['name' => 'PAID SERVICE',          'code' => 'PS',   'dept' => 'Service'],
            ['name' => 'BODYSHOP REPAIR',       'code' => 'BSR',  'dept' => 'Bodyshop'],
            ['name' => 'BODYSHOP INSURANCE',    'code' => 'BSI',  'dept' => 'Bodyshop'],
            ['name' => 'TYRE REPLACEMENT',      'code' => 'TYR',  'dept' => 'Tyre'],
            ['name' => 'WHEEL ALIGNMENT',       'code' => 'WA',   'dept' => 'Tyre'],
            ['name' => 'ACCESSORIES FITMENT',   'code' => 'ACC',  'dept' => 'Accessories'],
            ['name' => 'DETAILING',             'code' => 'DET',  'dept' => 'Detailing'],
            ['name' => 'CERAMIC COATING',       'code' => 'CER',  'dept' => 'Detailing'],
        ];

        foreach ($real as $row) {
            ServiceTypeMaster::firstOrCreate(
                ['name' => $row['name']],
                [
                    'code' => $row['code'],
                    'workshop_department_id' => $resolve($row['dept']),
                    'requires_advisor' => true,
                    'is_active' => true,
                ],
            );
        }
    }
}
