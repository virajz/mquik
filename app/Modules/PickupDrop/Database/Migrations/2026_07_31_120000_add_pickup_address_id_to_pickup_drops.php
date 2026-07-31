<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Link the pickup address to a saved customer address (live) rather than only
 * snapshotting it as free text. Mirrors the same column on `appointments`.
 * A custom/one-off pickup address still uses the free-text `pickup_address` column.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pickup_drops', function (Blueprint $table) {
            $table->foreignId('pickup_address_id')->nullable()->after('pickup_address')->constrained('customer_addresses')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('pickup_drops', function (Blueprint $table) {
            $table->dropConstrainedForeignId('pickup_address_id');
        });
    }
};
