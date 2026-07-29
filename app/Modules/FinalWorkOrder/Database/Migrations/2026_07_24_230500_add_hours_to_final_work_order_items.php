<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * FWR (module 28) captures item-wise technician hours on the work order.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('final_work_order_items', function (Blueprint $table) {
            $table->decimal('hours', 6, 2)->nullable()->after('result');
        });
    }

    public function down(): void
    {
        Schema::table('final_work_order_items', function (Blueprint $table) {
            $table->dropColumn('hours');
        });
    }
};
