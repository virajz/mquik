<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * GST classification codes. HSN covers goods (spares), SAC covers services
 * (labour); they share a shape, so one master holds both and `kind` separates
 * them for the two pickers.
 *
 * Unlike the other masters, the *code* is the identity here — the description
 * is prose and two codes may legitimately read similarly.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hsn_codes', function (Blueprint $table) {
            $table->id();
            // 4, 6 or 8 digits depending on turnover slab; SAC is always 6.
            $table->string('code', 8)->unique();
            $table->string('name');
            $table->string('kind', 4)->default('hsn'); // hsn | sac
            // Indicative GST rate; the authoritative rate still comes from the
            // tax slab picked on the line, this just drives sensible defaults.
            $table->decimal('gst_percent', 5, 2)->nullable();
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['is_active', 'kind']);
            $table->index('name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hsn_codes');
    }
};
