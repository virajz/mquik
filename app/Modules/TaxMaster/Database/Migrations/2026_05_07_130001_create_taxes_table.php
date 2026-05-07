<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('taxes', function (Blueprint $table) {
            $table->id();
            $table->string('name');                                        // e.g. "GST 18%" or "HSN 8708"
            $table->string('code', 20)->unique();                          // e.g. "GST18" or "HSN8708"
            $table->string('hsn_sac', 20)->nullable();                     // 4-8 digit HSN/SAC code
            $table->decimal('gst_percent', 5, 2)->default(0);              // 0.00 to 28.00 typically
            $table->decimal('cess_percent', 5, 2)->default(0);             // for special items like tobacco/luxury cars
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();

            // Same HSN can exist at different rates (e.g. NIL vs 5% vs 12%) — uniqueness is on the pair.
            $table->unique(['hsn_sac', 'gst_percent']);
            $table->index(['is_active', 'name']);
            $table->index('hsn_sac');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('taxes');
    }
};
