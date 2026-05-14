<?php

namespace App\Modules\LateMemo\Database\Seeders;

use App\Modules\LateMemo\Models\LateMemo;
use Illuminate\Database\Seeder;

class LateMemoSeeder extends Seeder
{
    public function run(): void
    {
        LateMemo::factory()->count(10)->create();
    }
}
