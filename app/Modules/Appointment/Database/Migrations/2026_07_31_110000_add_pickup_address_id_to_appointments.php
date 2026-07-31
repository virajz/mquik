<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Link the pickup address to a saved customer address instead of only snapshotting
 * it as free text. When a saved address is chosen, `pickup_address_id` is stored and
 * the text is resolved live from it (so later edits to the address propagate). A
 * one-off/custom address still uses the free-text `pickup_address` column.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->foreignId('pickup_address_id')->nullable()->after('pickup_address')->constrained('customer_addresses')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('pickup_address_id');
        });
    }
};
