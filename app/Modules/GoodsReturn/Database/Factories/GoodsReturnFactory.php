<?php

namespace App\Modules\GoodsReturn\Database\Factories;

use App\Modules\GoodsReturn\Models\GoodsReturn;
use App\Modules\VendorMaster\Models\VendorMaster;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GoodsReturn>
 */
class GoodsReturnFactory extends Factory
{
    protected $model = GoodsReturn::class;

    public function definition(): array
    {
        return [
            'vendor_id' => VendorMaster::factory(),
            'document_type' => 'credit_note',
            'credit_note_type' => 'tax_credit',
        ];
    }
}
