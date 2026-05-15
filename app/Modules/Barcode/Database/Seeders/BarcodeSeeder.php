<?php

namespace App\Modules\Barcode\Database\Seeders;

use App\Modules\Barcode\Models\Barcode;
use Illuminate\Database\Seeder;

class BarcodeSeeder extends Seeder
{
    public function run(): void
    {
        Barcode::factory()->count(10)->create();
    }
}
