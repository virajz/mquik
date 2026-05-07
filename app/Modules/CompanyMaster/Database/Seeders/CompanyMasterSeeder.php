<?php

namespace App\Modules\CompanyMaster\Database\Seeders;

use App\Modules\CompanyMaster\Models\CompanyMaster;
use Illuminate\Database\Seeder;

class CompanyMasterSeeder extends Seeder
{
    public function run(): void
    {
        if (CompanyMaster::query()->exists()) {
            return;
        }

        CompanyMaster::create([
            'legal_name' => 'MQUIK AUTO SERVICES PVT LTD',
            'trade_name' => 'MQUIK WORKSHOP',
            'code' => 'MQUIK',
            'gstin' => '24ABCDE1234F1Z5',
            'pan' => 'ABCDE1234F',
            'address' => 'BLOCK A, ISCON CROSS ROAD',
            'pincode' => '380015',
            'phone' => '9876543210',
            'email' => 'admin@mquik.com',
            'website' => 'https://mquik.com',
            'invoice_footer' => 'Thank you for choosing Mquik. Subject to Ahmedabad jurisdiction.',
            'is_active' => true,
        ]);
    }
}
