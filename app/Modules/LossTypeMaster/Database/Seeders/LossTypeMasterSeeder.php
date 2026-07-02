<?php

namespace App\Modules\LossTypeMaster\Database\Seeders;

use App\Modules\LossTypeMaster\Models\LossTypeMaster;
use Illuminate\Database\Seeder;

class LossTypeMasterSeeder extends Seeder
{
    public function run(): void
    {
        // Loss classification for proforma (warranty vs damaged/defective).
        $real = [
            ['name' => 'ONE SIDE WARRANTY',   'code' => 'OSW'],
            ['name' => 'DAMAGED / DEFECTIVE', 'code' => 'DMG'],
        ];

        foreach ($real as $type) {
            LossTypeMaster::firstOrCreate(
                ['name' => $type['name']],
                ['code' => $type['code'], 'is_active' => true],
            );
        }
    }
}
