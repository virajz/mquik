<?php

namespace App\Modules\PhotoTypeMaster\Database\Factories;

use App\Modules\PhotoTypeMaster\Models\PhotoTypeMaster;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PhotoTypeMaster>
 */
class PhotoTypeMasterFactory extends Factory
{
    protected $model = PhotoTypeMaster::class;

    public function definition(): array
    {
        return [
            'name' => strtoupper($this->faker->unique()->words(2, true)),
            'code' => null,
            'group' => strtoupper($this->faker->randomElement(['EXTERIOR', 'INTERIOR', 'METER', 'ENGINE', 'DOCUMENTS'])),
            'sort_order' => 0,
            'is_active' => true,
            'notes' => null,
        ];
    }

    public function inGroup(string $group, int $sortOrder = 0): static
    {
        return $this->state(fn () => ['group' => strtoupper($group), 'sort_order' => $sortOrder]);
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }

    public function withCode(string $code): static
    {
        return $this->state(fn () => ['code' => strtoupper($code)]);
    }
}
