<?php

namespace App\Modules\SalaryComponentMaster\Database\Seeders;

use App\Modules\SalaryComponentMaster\Models\SalaryComponentMaster;
use Illuminate\Database\Seeder;

class SalaryComponentMasterSeeder extends Seeder
{
    public function run(): void
    {
        $real = [
            // Earnings
            ['name' => 'BASIC SALARY',      'code' => 'BASIC', 'component_type' => 'earning',   'calc_method' => 'fixed',            'default_value' => 0,  'is_taxable' => true],
            ['name' => 'HRA',               'code' => 'HRA',   'component_type' => 'earning',   'calc_method' => 'percent_of_basic', 'default_value' => 40, 'is_taxable' => true],
            ['name' => 'CONVEYANCE',        'code' => 'CONV',  'component_type' => 'earning',   'calc_method' => 'fixed',            'default_value' => 0,  'is_taxable' => false],
            ['name' => 'MEDICAL ALLOWANCE', 'code' => 'MED',   'component_type' => 'earning',   'calc_method' => 'fixed',            'default_value' => 0,  'is_taxable' => false],
            ['name' => 'SPECIAL ALLOWANCE', 'code' => 'SPL',   'component_type' => 'earning',   'calc_method' => 'fixed',            'default_value' => 0,  'is_taxable' => true],
            // Deductions
            ['name' => 'PF',                'code' => 'PF',    'component_type' => 'deduction', 'calc_method' => 'percent_of_basic', 'default_value' => 12, 'is_taxable' => false],
            ['name' => 'ESI',               'code' => 'ESI',   'component_type' => 'deduction', 'calc_method' => 'percent_of_basic', 'default_value' => 0.75, 'is_taxable' => false],
            ['name' => 'PROFESSIONAL TAX',  'code' => 'PT',    'component_type' => 'deduction', 'calc_method' => 'fixed',            'default_value' => 200, 'is_taxable' => false],
            ['name' => 'TDS',               'code' => 'TDS',   'component_type' => 'deduction', 'calc_method' => 'fixed',            'default_value' => 0,  'is_taxable' => false],
            ['name' => 'LOAN RECOVERY',     'code' => 'LOAN',  'component_type' => 'deduction', 'calc_method' => 'fixed',            'default_value' => 0,  'is_taxable' => false],
        ];

        foreach ($real as $component) {
            SalaryComponentMaster::firstOrCreate(
                ['name' => $component['name']],
                [
                    'code' => $component['code'],
                    'component_type' => $component['component_type'],
                    'calc_method' => $component['calc_method'],
                    'default_value' => $component['default_value'],
                    'is_taxable' => $component['is_taxable'],
                    'is_active' => true,
                ],
            );
        }
    }
}
