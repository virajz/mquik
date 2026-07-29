<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Additional Findings are also raised on an Outside Labour Order (module 29).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('technician_findings', function (Blueprint $table) {
            $table->foreignId('outside_labour_order_id')->nullable()->after('final_work_order_id')->constrained('outside_labour_orders')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('technician_findings', function (Blueprint $table) {
            $table->dropConstrainedForeignId('outside_labour_order_id');
        });
    }
};
