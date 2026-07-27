<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Requirement row 19 — grow the thin Estimate Template master into the full
 * spec: effective date, category, vehicle applicability and a service/combo/AMC
 * package on the header; and rate / UOM / HSN / tax / inventory-group + a
 * free-text description per line (so a template carries pricing, not just a
 * list of parts). Plus a single brochure attachment.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('estimate_templates', function (Blueprint $table) {
            $table->date('effective_date')->nullable()->after('code');
            $table->string('category', 30)->nullable()->after('effective_date'); // pms / brake / suspension / clutch / denting / painting
            $table->foreignId('vehicle_brand_id')->nullable()->after('category')->constrained('vehicle_brands')->nullOnDelete();
            $table->foreignId('vehicle_model_id')->nullable()->after('vehicle_brand_id')->constrained('vehicle_models')->nullOnDelete();
            $table->foreignId('vehicle_variant_id')->nullable()->after('vehicle_model_id')->constrained('vehicle_variants')->nullOnDelete();
            $table->foreignId('service_package_id')->nullable()->after('vehicle_variant_id')->constrained('service_packages')->nullOnDelete();
            $table->string('brochure_path')->nullable()->after('service_package_id');
            $table->string('brochure_name')->nullable()->after('brochure_path');
        });

        Schema::table('estimate_template_items', function (Blueprint $table) {
            $table->foreignId('inventory_group_id')->nullable()->after('labour_id')->constrained('inventory_groups')->nullOnDelete();
            $table->foreignId('uom_id')->nullable()->after('inventory_group_id')->constrained('units_of_measure')->nullOnDelete();
            $table->foreignId('hsn_id')->nullable()->after('uom_id')->constrained('hsn_codes')->nullOnDelete();
            $table->foreignId('tax_id')->nullable()->after('hsn_id')->constrained('taxes')->nullOnDelete();
            $table->string('description')->nullable()->after('tax_id');
            $table->decimal('unit_rate', 12, 2)->nullable()->after('default_qty');
        });
    }

    public function down(): void
    {
        Schema::table('estimate_template_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('inventory_group_id');
            $table->dropConstrainedForeignId('uom_id');
            $table->dropConstrainedForeignId('hsn_id');
            $table->dropConstrainedForeignId('tax_id');
            $table->dropColumn(['description', 'unit_rate']);
        });

        Schema::table('estimate_templates', function (Blueprint $table) {
            $table->dropConstrainedForeignId('vehicle_brand_id');
            $table->dropConstrainedForeignId('vehicle_model_id');
            $table->dropConstrainedForeignId('vehicle_variant_id');
            $table->dropConstrainedForeignId('service_package_id');
            $table->dropColumn(['effective_date', 'category', 'brochure_path', 'brochure_name']);
        });
    }
};
