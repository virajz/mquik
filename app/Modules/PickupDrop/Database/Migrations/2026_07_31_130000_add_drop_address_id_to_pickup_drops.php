<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Link the drop address to a saved customer address (live), mirroring
 * `pickup_address_id`. A custom/one-off drop address still uses the free-text
 * `drop_address` column.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pickup_drops', function (Blueprint $table) {
            $table->foreignId('drop_address_id')->nullable()->after('drop_address')->constrained('customer_addresses')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('pickup_drops', function (Blueprint $table) {
            $table->dropConstrainedForeignId('drop_address_id');
        });
    }
};
