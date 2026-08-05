<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Shelf life on the spare, manufacturing date on the batch.
 *
 * Expiry is a property of a *batch*, not of the part — so the spare master
 * carries how long the part keeps (e.g. 24 months), and each receipt carries
 * the date that batch was made. Expiry is then derived at receipt rather than
 * typed, which is what stops it being guessed or skipped.
 *
 * The derived date stays editable: what is printed on the pack always wins.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('spares', function (Blueprint $table) {
            $table->unsignedSmallInteger('shelf_life_value')->nullable()->after('tracks_batch');
            $table->string('shelf_life_unit', 10)->nullable()->after('shelf_life_value');
        });

        Schema::table('purchase_entry_items', function (Blueprint $table) {
            $table->date('manufacturing_date')->nullable()->after('batch_no');
        });

        // Carried onto the stock layer so the batch's age travels with it.
        Schema::table('stock_entries', function (Blueprint $table) {
            $table->date('manufacturing_date')->nullable()->after('batch_no');
        });
    }

    public function down(): void
    {
        Schema::table('spares', function (Blueprint $table) {
            $table->dropColumn(['shelf_life_value', 'shelf_life_unit']);
        });

        Schema::table('purchase_entry_items', function (Blueprint $table) {
            $table->dropColumn('manufacturing_date');
        });

        Schema::table('stock_entries', function (Blueprint $table) {
            $table->dropColumn('manufacturing_date');
        });
    }
};
