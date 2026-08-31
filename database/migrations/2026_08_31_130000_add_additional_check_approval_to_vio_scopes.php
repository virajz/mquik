<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A technician can add checks they find necessary mid-job. Where that work is
 * chargeable it stops being their call alone — the advisor confirms it before
 * it can reach a bill, and who confirmed it is part of the record.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vehicle_inspection_order_scopes', function (Blueprint $table) {
            $table->boolean('is_chargeable')->default(false)->after('is_additional');
            $table->foreignId('approved_by_id')->nullable()->after('is_chargeable')->constrained('employees')->nullOnDelete();
            $table->timestamp('approved_at')->nullable()->after('approved_by_id');
        });
    }

    public function down(): void
    {
        Schema::table('vehicle_inspection_order_scopes', function (Blueprint $table) {
            $table->dropConstrainedForeignId('approved_by_id');
            $table->dropColumn(['is_chargeable', 'approved_at']);
        });
    }
};
