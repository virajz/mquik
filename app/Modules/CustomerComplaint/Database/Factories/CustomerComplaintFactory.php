<?php

namespace App\Modules\CustomerComplaint\Database\Factories;

use App\Modules\CustomerComplaint\Models\CustomerComplaint;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CustomerComplaint>
 */
class CustomerComplaintFactory extends Factory
{
    protected $model = CustomerComplaint::class;

    public function definition(): array
    {
        return [
            'complaint_type' => 'service_quality',
            'complaint_source' => 'phone_call',
            'priority' => 'normal',
            'status' => CustomerComplaint::STATUS_UNDER_INVESTIGATION,
            'opened_at' => now(),
        ];
    }

    public function resolved(): static
    {
        return $this->state(fn () => [
            'status' => CustomerComplaint::STATUS_RESOLVED,
            'resolution_type' => 'rework',
            'achieved_score' => 4,
            'closed_at' => now(),
        ]);
    }

    public function repeatJob(): static
    {
        return $this->state(fn () => ['complaint_type' => 'repeat_job']);
    }
}
