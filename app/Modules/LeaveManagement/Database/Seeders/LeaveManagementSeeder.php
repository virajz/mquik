<?php

namespace App\Modules\LeaveManagement\Database\Seeders;

use App\Modules\LeaveManagement\Models\LeaveManagement;
use Illuminate\Database\Seeder;

class LeaveManagementSeeder extends Seeder
{
    public function run(): void
    {
        LeaveManagement::factory()->count(10)->create();
    }
}
