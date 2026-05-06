<?php

namespace App\Modules\EnquirySourceMaster\Database\Seeders;

use App\Modules\EnquirySourceMaster\Models\EnquirySourceMaster;
use Illuminate\Database\Seeder;

class EnquirySourceMasterSeeder extends Seeder
{
    public function run(): void
    {
        // Real enquiry sources used by the workshop CRM.
        $real = [
            ['name' => 'WALK-IN',          'code' => 'WLK'],
            ['name' => 'PHONE',            'code' => 'PHN'],
            ['name' => 'WHATSAPP',         'code' => 'WAP'],
            ['name' => 'WEB',              'code' => 'WEB'],
            ['name' => 'REFERRAL',         'code' => 'REF'],
            ['name' => 'CAMPAIGN',         'code' => 'CMP'],
            ['name' => 'REPEAT CUSTOMER',  'code' => 'RPT'],
            ['name' => 'GOOGLE ADS',       'code' => 'GAD'],
            ['name' => 'FACEBOOK',         'code' => 'FB'],
            ['name' => 'INSTAGRAM',        'code' => 'IG'],
        ];

        foreach ($real as $source) {
            EnquirySourceMaster::firstOrCreate(
                ['name' => $source['name']],
                ['code' => $source['code'], 'is_active' => true],
            );
        }
    }
}
