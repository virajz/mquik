<?php

namespace App\Modules\OutsideLabourEntry\Database\Factories;

use App\Modules\OutsideLabourEntry\Models\OutsideLabourEntry;
use App\Modules\VendorMaster\Models\VendorMaster;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OutsideLabourEntry>
 */
class OutsideLabourEntryFactory extends Factory
{
    protected $model = OutsideLabourEntry::class;

    public function definition(): array
    {
        return [
            'vendor_id' => VendorMaster::factory(),
        ];
    }
}
