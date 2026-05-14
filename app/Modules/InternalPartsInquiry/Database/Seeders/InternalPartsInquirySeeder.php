<?php

namespace App\Modules\InternalPartsInquiry\Database\Seeders;

use App\Modules\InternalPartsInquiry\Models\InternalPartsInquiry;
use Illuminate\Database\Seeder;

class InternalPartsInquirySeeder extends Seeder
{
    public function run(): void
    {
        InternalPartsInquiry::factory()->count(10)->create();
    }
}
