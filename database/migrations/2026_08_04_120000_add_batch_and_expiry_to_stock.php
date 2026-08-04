<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Turns the flat stock ledger into FIFO layers.
 *
 * An IN entry (purchase, opening, return) IS a cost layer — it carries the rate
 * the goods came in at, plus an optional batch no / expiry date. An OUT entry
 * points at the layer it drew from via `layer_id`, which is what makes
 * cost-of-issue genuinely FIFO and lets a batch be traced from receipt to the
 * job card it went out on.
 *
 * `spares.tracks_batch` marks the parts that need batch/expiry captured at
 * receipt — oils, chemicals, paints. Everything else keeps working untouched.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stock_entries', function (Blueprint $table) {
            $table->string('batch_no', 40)->nullable()->after('rate_per_unit');
            $table->date('expiry_date')->nullable()->after('batch_no');
            // Self-reference: the IN entry this OUT entry consumed. Null on an
            // IN entry, and on an OUT entry issued against no remaining layer.
            $table->foreignId('layer_id')->nullable()->after('expiry_date')
                ->constrained('stock_entries')->nullOnDelete();

            $table->index(['spare_id', 'expiry_date']);
            $table->index(['spare_id', 'batch_no']);
        });

        Schema::table('spares', function (Blueprint $table) {
            $table->boolean('tracks_batch')->default(false)->after('is_active');
        });
    }

    public function down(): void
    {
        Schema::table('stock_entries', function (Blueprint $table) {
            $table->dropForeign(['layer_id']);
            $table->dropIndex(['spare_id', 'expiry_date']);
            $table->dropIndex(['spare_id', 'batch_no']);
            $table->dropColumn(['batch_no', 'expiry_date', 'layer_id']);
        });

        Schema::table('spares', function (Blueprint $table) {
            $table->dropColumn('tracks_batch');
        });
    }
};
