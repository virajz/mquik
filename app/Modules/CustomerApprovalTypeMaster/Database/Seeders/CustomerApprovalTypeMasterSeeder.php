<?php

namespace App\Modules\CustomerApprovalTypeMaster\Database\Seeders;

use App\Modules\CustomerApprovalTypeMaster\Models\CustomerApprovalTypeMaster;
use Illuminate\Database\Seeder;

class CustomerApprovalTypeMasterSeeder extends Seeder
{
    public function run(): void
    {
        // How a customer can authorise estimates / additional work on a job card.
        $real = [
            ['name' => 'IN PERSON',         'code' => 'PER'],
            ['name' => 'PHONE CALL',        'code' => 'PHN'],
            ['name' => 'WHATSAPP',          'code' => 'WA'],
            ['name' => 'EMAIL',             'code' => 'EML'],
            ['name' => 'SMS',               'code' => 'SMS'],
            ['name' => 'DIGITAL SIGNATURE', 'code' => 'SIGN'],
        ];

        foreach ($real as $type) {
            CustomerApprovalTypeMaster::firstOrCreate(
                ['name' => $type['name']],
                ['code' => $type['code'], 'is_active' => true],
            );
        }
    }
}
