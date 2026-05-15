<?php

namespace App\Modules\Inventory\Database\Factories;

use App\Modules\Inventory\Models\StockEntry;
use App\Modules\SpareMaster\Models\SpareMaster;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StockEntry>
 */
class StockEntryFactory extends Factory
{
    protected $model = StockEntry::class;

    public function definition(): array
    {
        return [
            'spare_id' => SpareMaster::factory(),
            'entry_type' => StockEntry::TYPE_PURCHASE,
            'qty' => fake()->randomFloat(2, 1, 100),
            'rate_per_unit' => fake()->randomFloat(2, 10, 5000),
            'moved_at' => fake()->dateTimeBetween('-30 days', 'now'),
        ];
    }

    public function in(float $qty = 10): static
    {
        return $this->state(fn () => [
            'entry_type' => StockEntry::TYPE_PURCHASE,
            'qty' => $qty,
        ]);
    }

    public function out(float $qty = 5): static
    {
        return $this->state(fn () => [
            'entry_type' => StockEntry::TYPE_SALE,
            'qty' => -$qty,
        ]);
    }

    public function opening(float $qty = 50): static
    {
        return $this->state(fn () => [
            'entry_type' => StockEntry::TYPE_OPENING,
            'qty' => $qty,
        ]);
    }
}
