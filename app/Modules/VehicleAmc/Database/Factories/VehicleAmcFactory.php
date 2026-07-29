<?php

namespace App\Modules\VehicleAmc\Database\Factories;

use App\Modules\VehicleAmc\Models\VehicleAmc;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<VehicleAmc>
 */
class VehicleAmcFactory extends Factory
{
    protected $model = VehicleAmc::class;

    public function definition(): array
    {
        return [
            'amc_package' => 'gold',
            'amc_validity' => '12_months',
            'services_limit' => 4,
            'services_availed' => 0,
            'status' => VehicleAmc::STATUS_ACTIVE,
            'payment_status' => 'fully_paid',
            'start_date' => now(),
            'end_date' => now()->addYear(),
            'amount' => $this->faker->numberBetween(5000, 30000),
        ];
    }

    public function expired(): static
    {
        return $this->state(fn () => ['status' => VehicleAmc::STATUS_EXPIRED, 'end_date' => now()->subDays(5)]);
    }

    public function renewalDue(): static
    {
        return $this->state(fn () => ['status' => VehicleAmc::STATUS_ACTIVE, 'end_date' => now()->addDays(5)]);
    }
}
