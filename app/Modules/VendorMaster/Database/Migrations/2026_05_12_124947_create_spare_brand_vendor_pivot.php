<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Vendors can supply multiple Spare Brands (e.g. a single vendor sells both
 * Bosch and Mahle filters). Pivot is alphabetical-Laravel-convention:
 * `spare_brand_vendor` so model relations can pick it up implicitly.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('spare_brand_vendor', function (Blueprint $table) {
            $table->foreignId('vendor_id')->constrained('vendors')->cascadeOnDelete();
            $table->foreignId('spare_brand_id')->constrained('spare_brands')->restrictOnDelete();
            $table->primary(['vendor_id', 'spare_brand_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('spare_brand_vendor');
    }
};
