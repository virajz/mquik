<?php

namespace App\Modules\BankMaster\Database\Seeders;

use App\Modules\BankMaster\Models\BankMaster;
use Illuminate\Database\Seeder;

class BankMasterSeeder extends Seeder
{
    public function run(): void
    {
        $real = [
            ['name' => 'HDFC BANK',            'code' => 'HDFC'],
            ['name' => 'ICICI BANK',           'code' => 'ICICI'],
            ['name' => 'STATE BANK OF INDIA',  'code' => 'SBI'],
            ['name' => 'AXIS BANK',            'code' => 'AXIS'],
            ['name' => 'KOTAK MAHINDRA BANK',  'code' => 'KOTAK'],
            ['name' => 'YES BANK',             'code' => 'YES'],
            ['name' => 'IDFC FIRST BANK',      'code' => 'IDFC'],
            ['name' => 'PUNJAB NATIONAL BANK', 'code' => 'PNB'],
            ['name' => 'BANK OF BARODA',       'code' => 'BOB'],
            ['name' => 'CANARA BANK',          'code' => 'CANARA'],
            ['name' => 'INDIAN BANK',          'code' => 'INDB'],
            ['name' => 'UNION BANK OF INDIA',  'code' => 'UBI'],
        ];

        foreach ($real as $row) {
            BankMaster::firstOrCreate(
                ['name' => $row['name']],
                ['code' => $row['code'], 'is_active' => true],
            );
        }
    }
}
