<?php

namespace App\Modules\DocumentRejectionReasonMaster\Database\Factories;

use App\Modules\DocumentRejectionReasonMaster\Models\DocumentRejectionReasonMaster;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DocumentRejectionReasonMaster>
 */
class DocumentRejectionReasonMasterFactory extends Factory
{
    protected $model = DocumentRejectionReasonMaster::class;

    public function definition(): array
    {
        return [
            'name' => strtoupper($this->faker->unique()->words(2, true)),
            'code' => null,
            'is_active' => true,
            'notes' => null,
        ];
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
