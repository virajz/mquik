<?php

namespace App\Modules\FollowUpModeMaster\Database\Seeders;

use App\Modules\FollowUpModeMaster\Models\FollowUpModeMaster;
use Illuminate\Database\Seeder;

class FollowUpModeMasterSeeder extends Seeder
{
    public function run(): void
    {
        $real = [
            ['name' => 'TELEPHONIC CALL', 'code' => 'CALL'],
            ['name' => 'SMS',            'code' => 'SMS'],
            ['name' => 'WHATSAPP',       'code' => 'WA'],
            ['name' => 'EMAIL',          'code' => 'EML'],
            ['name' => 'PHYSICAL VISIT', 'code' => 'VST'],
        ];

        foreach ($real as $row) {
            FollowUpModeMaster::firstOrCreate(['name' => $row['name']], ['code' => $row['code'], 'is_active' => true]);
        }
    }
}
