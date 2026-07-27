<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Module 17 — VPI (Vendor Purchase Inquiry) / RFQ.
 *
 * The store asks a vendor for part rate, brand, delivery time, warranty and
 * payment terms (a Request for Quotation). Header + requested-part lines
 * (each carrying the vendor's quote), additional-charge lines, and document
 * attachments (RFQ / quotation / approval note). Cross-vendor comparison is
 * done in the Purchase Report by raising one VPI per vendor.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vendor_purchase_inquiries', function (Blueprint $table) {
            $table->id();
            $table->string('vpi_no')->nullable()->unique();

            $table->string('inquiry_type', 30)->nullable(); // against_job_card / stock_replenishment / expense
            $table->foreignId('vendor_id')->nullable()->constrained('vendors')->nullOnDelete();
            $table->foreignId('job_card_id')->nullable()->constrained('job_cards')->nullOnDelete();
            $table->foreignId('employee_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->foreignId('priority_id')->nullable()->constrained('priorities')->nullOnDelete();
            $table->foreignId('revision_reason_id')->nullable()->constrained('estimate_revision_reasons')->nullOnDelete();

            $table->string('vendor_category', 20)->nullable();     // preferred / approved / backup
            $table->string('vendor_rating_type', 20)->nullable();  // quality / price / delivery / support
            $table->string('payment_term', 20)->nullable();        // advance / cod / credit_7 / credit_15 / credit_30
            $table->string('comparison_parameter', 30)->nullable(); // price / discount / lead_time / ...
            $table->string('approval_authority', 30)->nullable();  // service_advisor / store_manager / ...

            $table->string('tat_option', 20)->nullable(); // immediate / same_day / next_day / two_three_days / custom
            $table->unsignedSmallInteger('tat_custom_days')->nullable();

            $table->string('status', 20)->default('pending'); // pending / in_progress / completed / cancelled

            $table->text('terms_conditions')->nullable(); // T&Cs / SLA — delivery commitments, penalty rules
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index(['vendor_id', 'status']);
            $table->index('job_card_id');
        });

        Schema::create('vendor_purchase_inquiry_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_purchase_inquiry_id')->constrained()->cascadeOnDelete();
            $table->foreignId('spare_id')->nullable()->constrained('spares')->nullOnDelete();
            $table->foreignId('spare_brand_id')->nullable()->constrained('spare_brands')->nullOnDelete();
            $table->foreignId('part_type_id')->nullable()->constrained('part_types')->nullOnDelete(); // Genuine / Aftermarket
            $table->foreignId('uom_id')->nullable()->constrained('units_of_measure')->nullOnDelete();
            $table->foreignId('hsn_id')->nullable()->constrained('hsn_codes')->nullOnDelete();
            $table->foreignId('tax_id')->nullable()->constrained('taxes')->nullOnDelete();
            $table->foreignId('vehicle_variant_id')->nullable()->constrained('vehicle_variants')->nullOnDelete();

            $table->string('description');
            $table->decimal('quantity', 12, 2)->default(1);

            // Vendor's quote for this line.
            $table->decimal('quoted_rate', 12, 2)->nullable();
            $table->string('discount_type', 20)->nullable(); // line / scheme / cash
            $table->decimal('discount_value', 12, 2)->nullable();
            $table->string('warranty_type', 20)->nullable(); // no_warranty / vendor / manufacturer
            $table->unsignedSmallInteger('warranty_period_value')->nullable();
            $table->string('warranty_period_unit', 10)->nullable(); // month / day
            $table->unsignedSmallInteger('lead_time_days')->nullable(); // delivery time
            $table->string('stock_status', 20)->nullable(); // partially_available / fully_available / not_available
            $table->string('alternative_option', 20)->nullable(); // primary / alternate_part / alternate_brand

            $table->text('notes')->nullable();
            $table->unsignedSmallInteger('sequence_no')->default(1);
            $table->timestamps();

            $table->index(['vendor_purchase_inquiry_id', 'sequence_no'], 'vpi_items_inquiry_sequence_index');
        });

        Schema::create('vendor_purchase_inquiry_charges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_purchase_inquiry_id')->constrained()->cascadeOnDelete();
            $table->foreignId('charge_type_id')->nullable()->constrained('charge_types')->nullOnDelete();
            $table->decimal('amount', 12, 2)->default(0);
            $table->unsignedSmallInteger('sequence_no')->default(1);
            $table->timestamps();

            $table->index(['vendor_purchase_inquiry_id', 'sequence_no'], 'vpi_charges_inquiry_sequence_index');
        });

        Schema::create('vendor_purchase_inquiry_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_purchase_inquiry_id')->constrained()->cascadeOnDelete();
            $table->string('attachment_type', 20)->nullable(); // rfq_document / vendor_quotation / approval_note
            $table->string('kind', 10)->default('image'); // image / pdf
            $table->string('path');
            $table->string('original_name')->nullable();
            $table->unsignedInteger('size_bytes')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedSmallInteger('sequence_no')->default(1);
            $table->timestamps();

            $table->index(['vendor_purchase_inquiry_id', 'sequence_no'], 'vpi_attachments_inquiry_sequence_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vendor_purchase_inquiry_attachments');
        Schema::dropIfExists('vendor_purchase_inquiry_charges');
        Schema::dropIfExists('vendor_purchase_inquiry_items');
        Schema::dropIfExists('vendor_purchase_inquiries');
    }
};
