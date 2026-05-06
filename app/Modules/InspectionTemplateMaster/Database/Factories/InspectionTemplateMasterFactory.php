<?php

namespace App\Modules\InspectionTemplateMaster\Database\Factories;

use App\Modules\InspectionTemplateMaster\Models\InspectionTemplateMaster;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InspectionTemplateMaster>
 */
class InspectionTemplateMasterFactory extends Factory
{
    protected $model = InspectionTemplateMaster::class;

    public function definition(): array
    {
        return [
            'name' => strtoupper($this->faker->unique()->words(3, true)),
            'code' => null,
            'applies_to' => 'custom',
            'is_active' => true,
            'notes' => null,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }

    public function appliesTo(string $value): static
    {
        return $this->state(fn () => ['applies_to' => $value]);
    }
}
