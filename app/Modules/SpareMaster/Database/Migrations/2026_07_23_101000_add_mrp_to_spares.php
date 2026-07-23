<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Spares carry two prices: the printed MRP (tax-inclusive, what the customer
 * sees on the box) and the selling rate before tax, which already exists.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('spares', function (Blueprint $table) {
            $table->decimal('mrp', 12, 2)->nullable()->after('rate_before_tax');
        });
    }

    public function down(): void
    {
        Schema::table('spares', function (Blueprint $table) {
            $table->dropColumn('mrp');
        });
    }
};
