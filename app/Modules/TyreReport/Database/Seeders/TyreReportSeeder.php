<?php

namespace App\Modules\TyreReport\Database\Seeders;

use App\Modules\TyreReport\Models\TyreReport;
use Faker\Factory;
use Illuminate\Database\Seeder;

class TyreReportSeeder extends Seeder
{
    public function run(): void
    {
        // Sample rows need Faker (dev-only); skip on a --no-dev server deploy.
        if (! class_exists(Factory::class)) {
            return;
        }

        TyreReport::factory()->count(3)->withLines()->create();
    }
}
