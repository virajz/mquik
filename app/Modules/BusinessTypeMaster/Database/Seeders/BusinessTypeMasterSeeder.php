<?php

namespace App\Modules\BusinessTypeMaster\Database\Seeders;

use App\Modules\BusinessTypeMaster\Models\BusinessTypeMaster;
use Illuminate\Database\Seeder;

class BusinessTypeMasterSeeder extends Seeder
{
    public function run(): void
    {
        $real = [
            ['name' => 'WALKING',    'code' => 'WLK'],
            ['name' => 'LOYAL',      'code' => 'LYL'],
            ['name' => 'CORPORATE',  'code' => 'CORP'],
            ['name' => 'GOVERNMENT', 'code' => 'GOVT'],
        ];

        foreach ($real as $row) {
            BusinessTypeMaster::firstOrCreate(
                ['name' => $row['name']],
                ['code' => $row['code'], 'is_active' => true],
            );
        }
    }
}
