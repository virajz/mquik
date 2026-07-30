<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Module 81 — Service Package (pricing extension).
 *
 * Grows the existing lean package master into a full priced package builder:
 * per-line pricing on the included services, a new included-spares child with
 * the same pricing shape, package-level offer / net / saving roll-up, a
 * usage-rule + T&Cs, and brochure / note attachments.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('service_packages', function (Blueprint $table) {
            $table->string('usage_rule', 20)->nullable()->after('is_amc'); // one_time_use / one_vehicle_only / multi_use
            $table->text('terms_conditions')->nullable()->after('description');
            $table->decimal('net_price', 12, 2)->nullable()->after('total_price');   // MRP incl. tax
            $table->decimal('offer_price', 12, 2)->nullable()->after('net_price');
            $table->decimal('discount_percent', 5, 2)->nullable()->after('offer_price');
            $table->decimal('saving_price', 12, 2)->nullable()->after('discount_percent'); // profit / saving
            $table->string('remarks')->nullable()->after('saving_price');
        });

        // Per-line pricing on the included services.
        Schema::table('service_package_services', function (Blueprint $table) {
            $table->string('sac', 12)->nullable()->after('service_type_id');
            $table->decimal('rate', 12, 2)->nullable()->after('sac');
            $table->decimal('quantity', 12, 2)->default(1)->after('rate');
            $table->decimal('tax_percent', 5, 2)->nullable()->after('quantity');
            $table->decimal('taxable_value', 12, 2)->nullable()->after('tax_percent');
            $table->decimal('net_price', 12, 2)->nullable()->after('taxable_value');
            $table->decimal('offer_price', 12, 2)->nullable()->after('net_price');
            $table->decimal('discount_percent', 5, 2)->nullable()->after('offer_price');
            $table->decimal('saving_price', 12, 2)->nullable()->after('discount_percent');
        });

        // Included spares — the same pricing shape as a service line.
        Schema::create('service_package_spares', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_package_id')->constrained('service_packages')->cascadeOnDelete();
            $table->foreignId('spare_id')->nullable()->constrained('spares')->nullOnDelete();
            $table->foreignId('uom_id')->nullable()->constrained('units_of_measure')->nullOnDelete();
            $table->foreignId('hsn_id')->nullable()->constrained('hsn_codes')->nullOnDelete();
            $table->foreignId('tax_id')->nullable()->constrained('taxes')->nullOnDelete();

            $table->string('description');
            $table->string('sac', 12)->nullable(); // HSN/SAC free text
            $table->decimal('rate', 12, 2)->nullable();
            $table->decimal('quantity', 12, 2)->default(1);
            $table->decimal('tax_percent', 5, 2)->nullable();
            $table->decimal('taxable_value', 12, 2)->nullable();
            $table->decimal('net_price', 12, 2)->nullable();
            $table->decimal('offer_price', 12, 2)->nullable();
            $table->decimal('discount_percent', 5, 2)->nullable();
            $table->decimal('saving_price', 12, 2)->nullable();
            $table->string('remarks')->nullable();
            $table->unsignedSmallInteger('sequence_no')->default(1);
            $table->timestamps();

            $table->index(['service_package_id', 'sequence_no'], 'pkg_spares_pkg_sequence_index');
        });

        Schema::create('service_package_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_package_id')->constrained('service_packages')->cascadeOnDelete();
            $table->string('attachment_type', 20)->nullable(); // brochure / package_note
            $table->string('kind', 10)->default('image');
            $table->string('path');
            $table->string('original_name')->nullable();
            $table->unsignedInteger('size_bytes')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedSmallInteger('sequence_no')->default(1);
            $table->timestamps();

            $table->index(['service_package_id', 'sequence_no'], 'pkg_attachments_pkg_sequence_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_package_attachments');
        Schema::dropIfExists('service_package_spares');

        Schema::table('service_package_services', function (Blueprint $table) {
            $table->dropColumn(['sac', 'rate', 'quantity', 'tax_percent', 'taxable_value', 'net_price', 'offer_price', 'discount_percent', 'saving_price']);
        });

        Schema::table('service_packages', function (Blueprint $table) {
            $table->dropColumn(['usage_rule', 'terms_conditions', 'net_price', 'offer_price', 'discount_percent', 'saving_price', 'remarks']);
        });
    }
};
