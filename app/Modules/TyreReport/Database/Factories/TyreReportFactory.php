<?php

namespace App\Modules\TyreReport\Database\Factories;

use App\Modules\CustomerMaster\Models\CustomerMaster;
use App\Modules\CustomerVehicleMaster\Models\CustomerVehicleMaster;
use App\Modules\TyreReport\Models\TyreReport;
use App\Modules\TyreReport\Models\TyreReportLine;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TyreReport>
 */
class TyreReportFactory extends Factory
{
    protected $model = TyreReport::class;

    public function definition(): array
    {
        $customer = CustomerMaster::factory()->create();

        return [
            'customer_id' => $customer->id,
            'customer_vehicle_id' => CustomerVehicleMaster::factory()->create(['customer_id' => $customer->id])->id,
            'odometer_km' => $this->faker->numberBetween(5000, 120000),
            'reported_on' => now()->toDateString(),
        ];
    }

    /** Seed the five position lines a real report always carries. */
    public function withLines(): static
    {
        return $this->afterCreating(function (TyreReport $report) {
            $seq = 1;
            foreach (array_keys(TyreReport::POSITIONS) as $position) {
                TyreReportLine::create([
                    'tyre_report_id' => $report->id,
                    'position' => $position,
                    'tyre_size' => '205/45 R16',
                    'tread_depth_mm' => $this->faker->randomFloat(1, 1.5, 8),
                    'pressure_psi' => $this->faker->randomFloat(1, 28, 36),
                    'condition' => $this->faker->randomElement(array_keys(TyreReport::conditions())),
                    'sequence_no' => $seq++,
                ]);
            }
        });
    }
}
