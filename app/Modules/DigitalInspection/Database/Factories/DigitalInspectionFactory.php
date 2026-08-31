<?php

namespace App\Modules\DigitalInspection\Database\Factories;

use App\Modules\DigitalInspection\Models\DigitalInspection;
use App\Modules\EmployeeMaster\Models\EmployeeMaster;
use App\Modules\InspectionTemplateMaster\Models\InspectionTemplateMaster;
use App\Modules\JobCard\Models\JobCard;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DigitalInspection>
 */
class DigitalInspectionFactory extends Factory
{
    protected $model = DigitalInspection::class;

    public function definition(): array
    {
        return [
            'job_card_id' => JobCard::factory(),
            'inspection_template_id' => InspectionTemplateMaster::factory(),
            // A sheet always has somebody doing the work and somebody answering
            // for it, so a factory-made one can be reopened and saved.
            'assigned_technician_id' => EmployeeMaster::factory()->technician(),
            'floor_incharge_id' => EmployeeMaster::factory()->floorIncharge(),
            'status' => DigitalInspection::STATUS_PENDING,
        ];
    }

    public function wip(): static
    {
        return $this->state(fn () => [
            'status' => DigitalInspection::STATUS_WIP,
            'started_at' => now(),
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn () => [
            'status' => DigitalInspection::STATUS_COMPLETED,
            'started_at' => now()->subHours(2),
            'completed_at' => now(),
        ]);
    }
}
