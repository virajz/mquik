<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * "Additional Findings" are also raised during a Final Work Order (module 27),
 * not just a Vehicle Inspection Order. Add a nullable FWO link so the same
 * finding entity serves both.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('technician_findings', function (Blueprint $table) {
            $table->foreignId('final_work_order_id')->nullable()->after('vehicle_inspection_order_id')->constrained('final_work_orders')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('technician_findings', function (Blueprint $table) {
            $table->dropConstrainedForeignId('final_work_order_id');
        });
    }
};
