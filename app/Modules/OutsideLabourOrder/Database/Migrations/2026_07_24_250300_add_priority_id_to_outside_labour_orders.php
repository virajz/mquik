<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The VIO table gained `priority_id` via a cross-cutting PriorityMaster
 * migration; the cloned outside_labour_orders needs the same FK (its create
 * migration only carried the legacy `work_priority` string).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('outside_labour_orders', function (Blueprint $table) {
            $table->foreignId('priority_id')->nullable()->after('work_priority')->constrained('priorities')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('outside_labour_orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('priority_id');
        });
    }
};
