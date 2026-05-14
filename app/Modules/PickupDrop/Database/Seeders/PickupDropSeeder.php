<?php

namespace App\Modules\PickupDrop\Database\Seeders;

use App\Modules\PickupDrop\Models\PickupDrop;
use Illuminate\Database\Seeder;

class PickupDropSeeder extends Seeder
{
    public function run(): void
    {
        PickupDrop::factory()->count(10)->create();
    }
}
