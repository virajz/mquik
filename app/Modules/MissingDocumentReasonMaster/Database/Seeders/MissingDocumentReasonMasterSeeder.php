<?php

namespace App\Modules\MissingDocumentReasonMaster\Database\Seeders;

use App\Modules\MissingDocumentReasonMaster\Models\MissingDocumentReasonMaster;
use Illuminate\Database\Seeder;

class MissingDocumentReasonMasterSeeder extends Seeder
{
    public function run(): void
    {
        $real = [
            ['name' => 'DOCUMENT LOST',         'code' => 'LOST'],
            ['name' => 'CUSTOMER NOT SUBMITTED', 'code' => 'CNS'],
            ['name' => 'AWAITING FROM RTO',     'code' => 'RTO'],
            ['name' => 'MISPLACED',             'code' => 'MIS'],
        ];

        foreach ($real as $row) {
            MissingDocumentReasonMaster::firstOrCreate(['name' => $row['name']], ['code' => $row['code'], 'is_active' => true]);
        }
    }
}
