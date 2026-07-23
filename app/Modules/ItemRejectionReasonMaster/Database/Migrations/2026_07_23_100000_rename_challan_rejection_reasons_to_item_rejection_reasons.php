<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/**
 * The list was named for Challans, but Proforma, Challan Entry and Purchase
 * Entry all point at it — these are item-level rejection reasons, not
 * challan-specific ones. Rename to match what it actually holds.
 *
 * Foreign keys follow the rename, so no data or constraint fixing is needed.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('challan_rejection_reasons') && ! Schema::hasTable('item_rejection_reasons')) {
            Schema::rename('challan_rejection_reasons', 'item_rejection_reasons');
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('item_rejection_reasons') && ! Schema::hasTable('challan_rejection_reasons')) {
            Schema::rename('item_rejection_reasons', 'challan_rejection_reasons');
        }
    }
};
