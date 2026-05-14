<?php

namespace App\Modules\Appointment\Database\Seeders;

use App\Modules\Appointment\Models\Appointment;
use Illuminate\Database\Seeder;

class AppointmentSeeder extends Seeder
{
    public function run(): void
    {
        Appointment::factory()->count(10)->create();
    }
}
