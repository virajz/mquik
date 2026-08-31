<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * A work order is raised at a moment, and that moment is the advisor's to state.
 *
 * The inspection template moves off the order and onto the checklist item: one
 * order can carry a PMS checklist and a tyre checklist, and it is the technician
 * who knows which applies to what they are doing.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vehicle_inspection_orders', function (Blueprint $table) {
            $table->timestamp('ordered_at')->nullable()->after('order_no');
        });

        // Existing orders: raised when they were created.
        DB::statement('update vehicle_inspection_orders set ordered_at = coalesce(assigned_at, created_at) where ordered_at is null');

        Schema::table('vehicle_inspection_order_items', function (Blueprint $table) {
            $table->foreignId('inspection_template_id')->nullable()->after('vehicle_inspection_order_id')
                ->constrained('inspection_templates')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('vehicle_inspection_order_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('inspection_template_id');
        });

        Schema::table('vehicle_inspection_orders', function (Blueprint $table) {
            $table->dropColumn('ordered_at');
        });
    }
};
