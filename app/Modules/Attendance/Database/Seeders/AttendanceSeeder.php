<?php

namespace App\Modules\Attendance\Database\Seeders;

use App\Modules\Attendance\Models\Attendance;
use Illuminate\Database\Seeder;

class AttendanceSeeder extends Seeder
{
    public function run(): void
    {
        Attendance::factory()->count(10)->create();
    }
}
