<?php

namespace App\Modules\PurchaseEntry\Database\Factories;

use App\Modules\PurchaseEntry\Models\PurchaseEntry;
use App\Modules\VendorMaster\Models\VendorMaster;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PurchaseEntry>
 */
class PurchaseEntryFactory extends Factory
{
    protected $model = PurchaseEntry::class;

    public function definition(): array
    {
        return [
            'vendor_id' => VendorMaster::factory(),
            'invoice_type' => 'tax_invoice',
            'purchase_type' => 'stock',
            'inventory_status' => 'fully_received',
        ];
    }
}
