<?php

namespace App\Modules\IncentivePolicyMaster\Database\Seeders;

use App\Modules\IncentivePolicyMaster\Models\IncentivePolicyMaster;
use Illuminate\Database\Seeder;

class IncentivePolicyMasterSeeder extends Seeder
{
    public function run(): void
    {
        $real = [
            ['name' => 'LABOUR SALES INCENTIVE',   'code' => 'LAB',  'basis' => 'labour_sales',          'rate_percent' => 5],
            ['name' => 'PARTS SALES INCENTIVE',    'code' => 'PART', 'basis' => 'parts_sales',           'rate_percent' => 2],
            ['name' => 'CUSTOMER SATISFACTION',    'code' => 'CSAT', 'basis' => 'customer_satisfaction', 'rate_percent' => 0],
            ['name' => 'EFFICIENCY BONUS',         'code' => 'EFF',  'basis' => 'efficiency',            'rate_percent' => 0],
        ];

        foreach ($real as $policy) {
            IncentivePolicyMaster::firstOrCreate(
                ['name' => $policy['name']],
                [
                    'code' => $policy['code'],
                    'basis' => $policy['basis'],
                    'rate_percent' => $policy['rate_percent'],
                    'is_active' => true,
                ],
            );
        }
    }
}
