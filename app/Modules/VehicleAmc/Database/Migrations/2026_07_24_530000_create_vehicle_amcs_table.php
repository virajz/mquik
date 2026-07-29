<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Module 80 — Vehicle AMC.
 *
 * A vehicle Annual Maintenance Contract: package, validity, included services /
 * spares (with discounts), usage limit, payment and the active → expired /
 * renewed lifecycle. Uses an FY-based series `MQ/AMC/26-27/#####` via `fy_label`.
 * Header + included-item lines + document attachments (signed agreement, notes).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicle_amcs', function (Blueprint $table) {
            $table->id();
            $table->string('amc_no')->nullable()->unique();
            $table->string('fy_label', 10)->nullable(); // "26-27" — per-FY sequence anchor

            $table->foreignId('workshop_department_id')->nullable()->constrained('workshop_departments')->nullOnDelete();
            $table->foreignId('sold_by_id')->nullable()->constrained('employees')->nullOnDelete(); // advisor
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->foreignId('customer_vehicle_id')->nullable()->constrained('customer_vehicles')->nullOnDelete();

            $table->string('amc_package', 12)->nullable(); // silver / gold / platinum
            $table->string('amc_validity', 12)->nullable(); // 12_months / 24_months / 36_months
            $table->unsignedSmallInteger('services_limit')->nullable(); // 2 / 3 / 4 / 6
            $table->unsignedSmallInteger('services_availed')->default(0);
            $table->string('status', 12)->default('active'); // active / expired / renewed / lost / cancelled
            $table->string('payment_status', 15)->default('pending'); // pending / partially_paid / fully_paid / refunded

            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->decimal('amount', 12, 2)->nullable();

            $table->text('terms_conditions')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('fy_label');
            $table->index('end_date');
        });

        Schema::create('vehicle_amc_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicle_amc_id')->constrained()->cascadeOnDelete();
            $table->foreignId('spare_id')->nullable()->constrained('spares')->nullOnDelete();
            $table->foreignId('uom_id')->nullable()->constrained('units_of_measure')->nullOnDelete();
            $table->foreignId('hsn_id')->nullable()->constrained('hsn_codes')->nullOnDelete();
            $table->foreignId('tax_id')->nullable()->constrained('taxes')->nullOnDelete();

            $table->string('item_type', 10)->default('spare'); // spare / labour
            $table->string('description');
            $table->decimal('quantity', 12, 2)->default(1);
            $table->decimal('discount_value', 12, 2)->nullable();

            $table->text('notes')->nullable();
            $table->unsignedSmallInteger('sequence_no')->default(1);
            $table->timestamps();

            $table->index(['vehicle_amc_id', 'sequence_no'], 'amc_items_amc_sequence_index');
        });

        Schema::create('vehicle_amc_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicle_amc_id')->constrained()->cascadeOnDelete();
            $table->string('attachment_type', 20)->nullable(); // signed_agreement / advisor_note / customer_note
            $table->string('kind', 10)->default('image');
            $table->string('path');
            $table->string('original_name')->nullable();
            $table->unsignedInteger('size_bytes')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedSmallInteger('sequence_no')->default(1);
            $table->timestamps();

            $table->index(['vehicle_amc_id', 'sequence_no'], 'amc_attachments_amc_sequence_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_amc_attachments');
        Schema::dropIfExists('vehicle_amc_items');
        Schema::dropIfExists('vehicle_amcs');
    }
};
