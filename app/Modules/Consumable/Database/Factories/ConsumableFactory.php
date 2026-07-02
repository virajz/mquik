<?php

namespace App\Modules\Consumable\Database\Factories;

use App\Modules\Consumable\Models\Consumable;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Consumable>
 */
class ConsumableFactory extends Factory
{
    protected $model = Consumable::class;

    public function definition(): array
    {
        return [
            'approval_status' => 'requested',
        ];
    }
}
