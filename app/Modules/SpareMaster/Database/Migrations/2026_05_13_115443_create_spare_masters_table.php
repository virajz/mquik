<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('spares', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('spare_code', 64)->nullable();
            $table->text('description')->nullable();
            $table->string('hsn_code', 16)->nullable();

            $table->foreignId('spare_brand_id')->nullable()->constrained('spare_brands')->nullOnDelete();
            $table->foreignId('tax_id')->nullable()->constrained('taxes')->nullOnDelete();
            $table->foreignId('inventory_group_id')->nullable()->constrained('inventory_groups')->nullOnDelete();
            $table->foreignId('inventory_sub_group_id')->nullable()->constrained('inventory_groups')->nullOnDelete();
            $table->foreignId('workshop_department_id')->nullable()->constrained('workshop_departments')->nullOnDelete();
            $table->foreignId('uom_id')->nullable()->constrained('units_of_measure')->nullOnDelete();

            $table->decimal('rate_before_tax', 12, 2)->default(0);
            $table->decimal('min_qty', 12, 2)->default(0);
            $table->decimal('max_qty', 12, 2)->default(0);

            $table->string('barcode_type', 16)->nullable();
            $table->string('location', 64)->nullable();

            $table->boolean('is_tyre')->default(false);
            $table->string('tyre_dimension', 32)->nullable();
            $table->string('rim_size', 16)->nullable();
            $table->string('load_speed_index', 16)->nullable();
            $table->string('tread_pattern', 32)->nullable();

            $table->text('remark')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique('spare_code');
            $table->index('name');
            $table->index('hsn_code');
            $table->index(['is_active', 'name']);
            $table->index(['is_tyre', 'is_active']);
        });

        Schema::create('spare_vehicle_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('spare_id')->constrained('spares')->cascadeOnDelete();
            $table->foreignId('vehicle_variant_id')->constrained('vehicle_variants')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['spare_id', 'vehicle_variant_id']);
            $table->index('vehicle_variant_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('spare_vehicle_variants');
        Schema::dropIfExists('spares');
    }
};
