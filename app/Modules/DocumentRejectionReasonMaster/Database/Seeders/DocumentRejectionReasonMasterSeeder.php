<?php

namespace App\Modules\DocumentRejectionReasonMaster\Database\Seeders;

use App\Modules\DocumentRejectionReasonMaster\Models\DocumentRejectionReasonMaster;
use Illuminate\Database\Seeder;

class DocumentRejectionReasonMasterSeeder extends Seeder
{
    public function run(): void
    {
        $real = [
            ['name' => 'EXPIRED DOCUMENT',  'code' => 'EXP'],
            ['name' => 'UNREADABLE COPY',   'code' => 'UNR'],
            ['name' => 'INVALID DOCUMENT',  'code' => 'INV'],
            ['name' => 'WRONG DOCUMENT',    'code' => 'WRG'],
        ];

        foreach ($real as $row) {
            DocumentRejectionReasonMaster::firstOrCreate(['name' => $row['name']], ['code' => $row['code'], 'is_active' => true]);
        }
    }
}
