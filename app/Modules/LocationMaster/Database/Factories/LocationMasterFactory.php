<?php

namespace App\Modules\LocationMaster\Database\Factories;

use App\Modules\LocationMaster\Models\LocationMaster;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<LocationMaster>
 */
class LocationMasterFactory extends Factory
{
    protected $model = LocationMaster::class;

    public function definition(): array
    {
        return [
            'name' => strtoupper($this->faker->company()).' BRANCH',
            'code' => strtoupper(Str::random(6)),
            'is_head_office' => false,
            'address' => null,
            'city_id' => null,
            'state_id' => null,
            'pincode' => null,
            'phone' => null,
            'email' => null,
            'gstin' => null,
            'is_active' => true,
            'notes' => null,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }

    public function headOffice(): static
    {
        return $this->state(fn () => ['is_head_office' => true]);
    }
}
