<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Dated purchase-rate history for a spare. The old ERP carried one row per
 * price revision (its `WEF` column), all sharing a part number — that history
 * cannot live on `spares`, which holds a single current rate. Each revision
 * lands here; the spare keeps the latest one.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('spare_rate_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('spare_id')->constrained('spares')->cascadeOnDelete();
            $table->foreignId('spare_brand_id')->nullable()->constrained('spare_brands')->nullOnDelete();
            $table->decimal('rate_before_tax', 12, 2)->default(0);
            $table->decimal('mrp', 12, 2)->nullable();
            $table->date('effective_from')->nullable();
            $table->string('source', 20)->default('legacy_import');
            $table->string('remark', 255)->nullable();
            $table->timestamps();

            $table->index(['spare_id', 'effective_from']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('spare_rate_history');
    }
};
