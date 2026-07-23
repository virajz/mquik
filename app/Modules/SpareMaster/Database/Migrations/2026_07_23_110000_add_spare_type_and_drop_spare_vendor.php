<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Spare classification, replacing the `is_tyre` boolean.
 *
 * A spare is one of three things, and the answer drives the rest of the form:
 *   vehicle_specific — needs a vehicle-compatibility list
 *   tyre            — needs the tyre size fields, no compatibility list
 *   common          — fits anything, so no compatibility list either
 *
 * `is_tyre` could not express "common", and could contradict the compatibility
 * list, so it is folded into the new column.
 *
 * Suppliers are also dropped from the spare: vendors are tracked against the
 * parts *brand* (`spare_brand_vendor`), which is where the client actually
 * negotiates, rather than against every individual part number.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('spares', function (Blueprint $table) {
            $table->string('spare_type', 20)->default('vehicle_specific')->after('spare_code');
            $table->index(['spare_type', 'is_active']);
        });

        // Existing tyres keep their meaning; everything else is vehicle-specific,
        // which is what is_tyre=false implied.
        DB::table('spares')->where('is_tyre', true)->update(['spare_type' => 'tyre']);

        Schema::table('spares', function (Blueprint $table) {
            // SQLite refuses to drop a column an index still references, so the
            // old [is_tyre, is_active] index has to go first. Postgres would
            // cascade this for us — the tests would not.
            $table->dropIndex(['is_tyre', 'is_active']);
            $table->dropColumn('is_tyre');
        });

        Schema::dropIfExists('spare_vendor');
    }

    public function down(): void
    {
        Schema::create('spare_vendor', function (Blueprint $table) {
            $table->id();
            $table->foreignId('spare_id')->constrained('spares')->cascadeOnDelete();
            $table->foreignId('vendor_id')->constrained('vendors')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['spare_id', 'vendor_id']);
        });

        Schema::table('spares', function (Blueprint $table) {
            $table->boolean('is_tyre')->default(false);
            $table->index(['is_tyre', 'is_active']);
        });

        DB::table('spares')->where('spare_type', 'tyre')->update(['is_tyre' => true]);

        Schema::table('spares', function (Blueprint $table) {
            $table->dropIndex(['spare_type', 'is_active']);
            $table->dropColumn('spare_type');
        });
    }
};
