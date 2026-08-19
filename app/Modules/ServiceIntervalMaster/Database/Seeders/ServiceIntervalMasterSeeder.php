<?php

namespace App\Modules\ServiceIntervalMaster\Database\Seeders;

use App\Modules\ServiceIntervalMaster\Models\ServiceIntervalMaster;
use Illuminate\Database\Seeder;

class ServiceIntervalMasterSeeder extends Seeder
{
    /**
     * Common intervals, as a starting point for the workshop to adjust.
     *
     * Cosmetic jobs are listed with no bound on purpose: a car wash is never
     * "overdue", and flagging it would bury the services that are.
     */
    public function run(): void
    {
        $intervals = [
            ['ENGINE OIL REPLACE', 6, 10000],
            ['OIL CHANGE', 6, 10000],
            ['PMS', 6, 10000],
            ['GENERAL SERVICE', 12, 15000],
            ['WHEEL ALIGNMENT', 6, 10000],
            ['WHEEL BALANCING', 6, 10000],
            ['TYRE ROTATION', 6, 10000],
            ['BRAKE FLUID REPLACEMENT', 24, 40000],
            ['COOLANT REPLACE', 24, 40000],
            ['AC CHECK', 12, null],
            ['AC SERVICE', 12, null],
            ['BATTERY CHECK', 12, null],
            ['BATTERY REPLACE', 48, null],
            ['SUSPENSION CHECK - FR', 12, 20000],
            ['CAR WASH', null, null],
            ['DETAILING', null, null],
        ];

        foreach ($intervals as [$name, $months, $km]) {
            ServiceIntervalMaster::firstOrCreate(
                ['name' => $name],
                ['interval_months' => $months, 'interval_km' => $km, 'is_active' => true],
            );
        }
    }
}
