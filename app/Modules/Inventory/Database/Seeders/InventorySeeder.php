<?php

namespace App\Modules\Inventory\Database\Seeders;

use App\Modules\Inventory\Models\Inventory;
use Illuminate\Database\Seeder;

class InventorySeeder extends Seeder
{
    public function run(): void
    {
        Inventory::factory()->count(10)->create();
    }
}
