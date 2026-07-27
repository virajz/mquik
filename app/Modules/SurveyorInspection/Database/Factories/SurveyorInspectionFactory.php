<?php

namespace App\Modules\SurveyorInspection\Database\Factories;

use App\Modules\JobCard\Models\JobCard;
use App\Modules\SurveyorInspection\Models\SurveyorInspection;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SurveyorInspection>
 */
class SurveyorInspectionFactory extends Factory
{
    protected $model = SurveyorInspection::class;

    public function definition(): array
    {
        return [
            'job_card_id' => JobCard::factory(),
            'surveyor_name' => strtoupper($this->faker->name()),
            'survey_type' => 'preliminary',
            'status' => SurveyorInspection::STATUS_PENDING,
        ];
    }

    public function inProgress(): static
    {
        return $this->state(fn () => ['status' => SurveyorInspection::STATUS_IN_PROGRESS]);
    }

    public function completed(): static
    {
        return $this->state(fn () => [
            'status' => SurveyorInspection::STATUS_COMPLETED,
            'surveyor_approval' => 'repair',
            'surveyed_at' => now(),
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn () => ['status' => SurveyorInspection::STATUS_CANCELLED]);
    }
}
