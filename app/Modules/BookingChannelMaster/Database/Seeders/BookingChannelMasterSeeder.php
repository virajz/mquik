<?php

namespace App\Modules\BookingChannelMaster\Database\Seeders;

use App\Modules\BookingChannelMaster\Models\BookingChannelMaster;
use Illuminate\Database\Seeder;

class BookingChannelMasterSeeder extends Seeder
{
    public function run(): void
    {
        // How the booking reached the workshop — drives the channel-wise CRM report.
        $real = [
            ['name' => 'WALK-IN',    'code' => 'WALK'],
            ['name' => 'PHONE CALL', 'code' => 'PHONE'],
            ['name' => 'WEBSITE',    'code' => 'WEB'],
            ['name' => 'MOBILE APP', 'code' => 'APP'],
            ['name' => 'WHATSAPP',   'code' => 'WA'],
            ['name' => 'EMAIL',      'code' => 'EMAIL'],
            ['name' => 'CRM',        'code' => 'CRM'],
            ['name' => 'REFERRAL',   'code' => 'REF'],
        ];

        foreach ($real as $row) {
            BookingChannelMaster::firstOrCreate(
                ['name' => $row['name']],
                ['code' => $row['code'], 'is_active' => true],
            );
        }
    }
}
