<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Module 59 — Invoice Correction Request / Response.
 *
 * The service advisor requests a correction to an already-raised invoice
 * (billing name / GST / address / vehicle / qty / rate / add-remove line /
 * discount / tax etc.); admin approves, and the invoice is corrected, credit-
 * noted-and-reissued, or cancelled-and-reissued. Header + item-wise correction
 * lines (each carrying the old vs new value) + document attachments.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoice_corrections', function (Blueprint $table) {
            $table->id();
            $table->string('correction_no')->nullable()->unique();

            $table->string('correction_request_type', 40)->nullable();
            $table->string('correction_reason', 30)->nullable();
            $table->string('priority', 10)->default('normal');   // normal / high / urgent
            $table->string('billing_action', 30)->nullable();    // correct_existing / credit_note_reissue / cancel_reissue
            $table->string('invoice_type', 20)->nullable();      // regular / insurance / counter_sales / qcare
            $table->string('invoice_reference')->nullable();     // invoice no / print-preview reference
            $table->string('status', 20)->default('requested');  // requested / under_review / on_hold / approved / corrected / rejected
            $table->string('rejection_reason', 30)->nullable();  // verbal_clarification / management_decision / other

            $table->foreignId('job_card_id')->nullable()->constrained('job_cards')->nullOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->foreignId('customer_vehicle_id')->nullable()->constrained('customer_vehicles')->nullOnDelete();
            $table->foreignId('insurance_company_id')->nullable()->constrained('insurance_companies')->nullOnDelete();
            $table->foreignId('workshop_department_id')->nullable()->constrained('workshop_departments')->nullOnDelete();
            $table->foreignId('service_type_id')->nullable()->constrained('service_types')->nullOnDelete();
            $table->foreignId('advisor_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->foreignId('mistake_by_id')->nullable()->constrained('employees')->nullOnDelete();

            $table->dateTime('requested_at')->nullable();
            $table->dateTime('approved_at')->nullable();
            $table->dateTime('corrected_at')->nullable();

            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('invoice_type');
            $table->index('created_at');
        });

        Schema::create('invoice_correction_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_correction_id')->constrained()->cascadeOnDelete();
            $table->foreignId('spare_id')->nullable()->constrained('spares')->nullOnDelete();
            $table->foreignId('uom_id')->nullable()->constrained('units_of_measure')->nullOnDelete();
            $table->foreignId('hsn_id')->nullable()->constrained('hsn_codes')->nullOnDelete();
            $table->foreignId('tax_id')->nullable()->constrained('taxes')->nullOnDelete();

            $table->string('item_type', 10)->default('spare'); // spare / labour
            $table->string('description');
            $table->decimal('quantity', 12, 2)->nullable();
            $table->decimal('rate', 12, 2)->nullable();
            $table->string('old_value')->nullable();
            $table->string('new_value')->nullable();
            $table->text('other_note')->nullable();

            $table->unsignedSmallInteger('sequence_no')->default(1);
            $table->timestamps();

            $table->index(['invoice_correction_id', 'sequence_no'], 'ic_items_correction_sequence_index');
        });

        Schema::create('invoice_correction_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_correction_id')->constrained()->cascadeOnDelete();
            $table->string('attachment_type', 30)->nullable(); // customer_request_screenshot / gst_certificate / original_invoice_copy / revised_invoice_copy
            $table->string('kind', 10)->default('image');
            $table->string('path');
            $table->string('original_name')->nullable();
            $table->unsignedInteger('size_bytes')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedSmallInteger('sequence_no')->default(1);
            $table->timestamps();

            $table->index(['invoice_correction_id', 'sequence_no'], 'ic_attachments_correction_sequence_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_correction_attachments');
        Schema::dropIfExists('invoice_correction_items');
        Schema::dropIfExists('invoice_corrections');
    }
};
