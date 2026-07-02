<?php

namespace App\Modules\FinalInspection\Database\Factories;

use App\Modules\FinalInspection\Models\FinalInspection;
use App\Modules\JobCard\Models\JobCard;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FinalInspection>
 */
class FinalInspectionFactory extends Factory
{
    protected $model = FinalInspection::class;

    public function definition(): array
    {
        return [
            'job_card_id' => JobCard::factory(),
            'status' => 'pending',
        ];
    }
}
