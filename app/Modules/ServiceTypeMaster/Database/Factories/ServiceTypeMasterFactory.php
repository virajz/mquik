<?php

namespace App\Modules\ServiceTypeMaster\Database\Factories;

use App\Modules\ServiceTypeMaster\Models\ServiceTypeMaster;
use App\Modules\WorkshopDepartmentMaster\Models\WorkshopDepartmentMaster;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ServiceTypeMaster>
 */
class ServiceTypeMasterFactory extends Factory
{
    protected $model = ServiceTypeMaster::class;

    public function definition(): array
    {
        return [
            'name' => strtoupper($this->faker->unique()->words(2, true)),
            'code' => strtoupper($this->faker->unique()->bothify('???###')),
            'workshop_department_id' => WorkshopDepartmentMaster::factory(),
            'requires_advisor' => true,
            'is_active' => true,
            'notes' => null,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }

    public function bodyshop(): static
    {
        return $this->state(fn () => [
            'workshop_department_id' => WorkshopDepartmentMaster::firstOrCreate(['name' => 'BODYSHOP'], ['is_active' => true])->id,
        ]);
    }
}
