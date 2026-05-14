<?php

namespace App\Modules\GateInOut\Database\Seeders;

use App\Modules\GateInOut\Models\GateInOut;
use Illuminate\Database\Seeder;

class GateInOutSeeder extends Seeder
{
    public function run(): void
    {
        GateInOut::factory()->count(10)->create();
    }
}
