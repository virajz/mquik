<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Optional manufacturing / expiry dates on the spare itself.
 *
 * These are the part's own dates — useful for a workshop that stocks a single
 * lot of an item and does not want per-batch tracking. They also seed the
 * defaults on a purchase line. Batch-level dates on `stock_entries` remain the
 * authority once batch tracking is switched on.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('spares', function (Blueprint $table) {
            $table->date('manufacturing_date')->nullable()->after('shelf_life_unit');
            $table->date('expiry_date')->nullable()->after('manufacturing_date');
            $table->index('expiry_date');
        });
    }

    public function down(): void
    {
        Schema::table('spares', function (Blueprint $table) {
            $table->dropIndex(['expiry_date']);
            $table->dropColumn(['manufacturing_date', 'expiry_date']);
        });
    }
};
