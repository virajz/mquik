<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Evidence belongs to the work, not to a checklist tick: a clutch replacement
 * needs several before and after shots, and they hang off the scope line that
 * was actually done. Completion type is likewise per line — one order can have
 * a job completed and another reworked.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vehicle_inspection_order_scopes', function (Blueprint $table) {
            $table->string('completion_type')->nullable()->after('completed_at');
        });

        Schema::create('vehicle_inspection_order_scope_photos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicle_inspection_order_scope_id')->constrained('vehicle_inspection_order_scopes', 'id', 'vio_scope_photos_scope_id_fk')->cascadeOnDelete();
            // 'before' or 'after' — the pair a technician is asked to produce.
            $table->string('stage', 10);
            $table->string('path');
            $table->string('original_name')->nullable();
            $table->unsignedBigInteger('size_bytes')->nullable();
            $table->string('notes')->nullable();
            $table->unsignedSmallInteger('sequence_no')->default(0);
            $table->timestamps();

            $table->index(['vehicle_inspection_order_scope_id', 'stage'], 'vio_scope_photos_scope_stage_idx');
        });

        // A pause is the technician's, and its reason is theirs to give.
        Schema::table('vehicle_inspection_order_pauses', function (Blueprint $table) {
            $table->foreignId('vehicle_inspection_order_scope_id')->nullable()->after('vehicle_inspection_order_id')
                ->constrained('vehicle_inspection_order_scopes', 'id', 'vio_pauses_scope_id_fk')->nullOnDelete();
            $table->foreignId('paused_by_id')->nullable()->after('resumed_at')->constrained('employees')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('vehicle_inspection_order_pauses', function (Blueprint $table) {
            $table->dropConstrainedForeignId('paused_by_id');
            $table->dropForeign('vio_pauses_scope_id_fk');
            $table->dropColumn('vehicle_inspection_order_scope_id');
        });

        Schema::dropIfExists('vehicle_inspection_order_scope_photos');

        Schema::table('vehicle_inspection_order_scopes', function (Blueprint $table) {
            $table->dropColumn('completion_type');
        });
    }
};
