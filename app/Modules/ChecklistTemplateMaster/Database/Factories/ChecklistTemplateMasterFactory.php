<?php

namespace App\Modules\ChecklistTemplateMaster\Database\Factories;

use App\Modules\ChecklistGroupMaster\Models\ChecklistGroupMaster;
use App\Modules\ChecklistTemplateMaster\Models\ChecklistTemplateMaster;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ChecklistTemplateMaster>
 */
class ChecklistTemplateMasterFactory extends Factory
{
    protected $model = ChecklistTemplateMaster::class;

    public function definition(): array
    {
        return [
            'name' => strtoupper($this->faker->unique()->words(3, true)),
            'code' => null,
            'checklist_group_id' => ChecklistGroupMaster::factory(),
            'applies_to' => 'generic',
            'items' => [
                ['label' => 'ITEM ONE', 'is_required' => true],
                ['label' => 'ITEM TWO', 'is_required' => false],
            ],
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

    public function forGroup(ChecklistGroupMaster $group): static
    {
        return $this->state(fn () => ['checklist_group_id' => $group->id]);
    }
}
