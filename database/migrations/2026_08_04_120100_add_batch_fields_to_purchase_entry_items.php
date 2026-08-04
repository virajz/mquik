<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Batch no / expiry captured at receipt, for the parts that need it (oils,
 * chemicals, paints). These flow straight onto the stock layer the purchase
 * creates, which is what makes the expiry alert and batch tracing work.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_entry_items', function (Blueprint $table) {
            $table->string('batch_no', 40)->nullable()->after('unit_rate');
            $table->date('expiry_date')->nullable()->after('batch_no');
        });
    }

    public function down(): void
    {
        Schema::table('purchase_entry_items', function (Blueprint $table) {
            $table->dropColumn(['batch_no', 'expiry_date']);
        });
    }
};
