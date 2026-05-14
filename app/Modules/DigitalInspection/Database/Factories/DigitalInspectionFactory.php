<?php

namespace App\Modules\DigitalInspection\Database\Factories;

use App\Modules\DigitalInspection\Models\DigitalInspection;
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
