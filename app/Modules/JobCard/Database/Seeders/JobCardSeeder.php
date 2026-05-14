<?php

namespace App\Modules\JobCard\Database\Seeders;

use App\Modules\JobCard\Models\JobCard;
use Illuminate\Database\Seeder;

class JobCardSeeder extends Seeder
{
    public function run(): void
    {
        JobCard::factory()->count(10)->create();
    }
}
