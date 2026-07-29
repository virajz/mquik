<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Module 44 — Outside Labour Credit Note / Debit Note.
 *
 * A CN / DN raised against an outside-labour bill / warranty return to settle
 * an adjustment (rework, defect, billing correction) commercially. Header +
 * item-wise labour lines (each pointing at the OL bill) + document attachments
 * (vendor bill copy / warranty card).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('outside_labour_credit_notes', function (Blueprint $table) {
            $table->id();
            $table->string('note_no')->nullable()->unique();

            $table->string('note_type', 20)->default('credit_note'); // credit_note / debit_note
            $table->string('invoice_type', 20)->nullable();           // e_credit_note / tax_credit_note / bill_of_supply / bill_book_memo

            $table->foreignId('vendor_id')->nullable()->constrained('vendors')->nullOnDelete();
            $table->foreignId('transport_company_id')->nullable()->constrained('vendors')->nullOnDelete();
            $table->foreignId('service_specialist_id')->nullable()->constrained('service_specialists')->nullOnDelete(); // service category
            $table->foreignId('outside_labour_return_id')->nullable()->constrained('outside_labour_returns')->nullOnDelete();
            $table->foreignId('job_card_id')->nullable()->constrained('job_cards')->nullOnDelete();
            $table->foreignId('customer_vehicle_id')->nullable()->constrained('customer_vehicles')->nullOnDelete();
            $table->foreignId('workshop_department_id')->nullable()->constrained('workshop_departments')->nullOnDelete();
            $table->foreignId('advisor_id')->nullable()->constrained('employees')->nullOnDelete();

            $table->string('return_reason', 40)->nullable();
            $table->string('commercial_settlement', 20)->nullable(); // partial / full
            $table->string('warranty_type', 20)->nullable();         // vendor / manufacturer
            $table->string('warranty_period', 20)->nullable();       // 3_months / 6_months / 12_months / 24_months
            $table->string('status', 20)->default('posted');         // posted / cancelled
            $table->string('vendor_rating_type', 20)->nullable();

            $table->decimal('amount', 12, 2)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index(['vendor_id', 'note_type']);
        });

        Schema::create('outside_labour_credit_note_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('outside_labour_credit_note_id')->constrained()->cascadeOnDelete();
            $table->foreignId('outside_labour_bill_id')->nullable()->constrained('outside_labour_bills')->nullOnDelete();
            $table->foreignId('uom_id')->nullable()->constrained('units_of_measure')->nullOnDelete();
            $table->foreignId('hsn_id')->nullable()->constrained('hsn_codes')->nullOnDelete();
            $table->foreignId('tax_id')->nullable()->constrained('taxes')->nullOnDelete();

            $table->string('description');
            $table->decimal('quantity', 12, 2)->default(1);
            $table->decimal('rate', 12, 2)->nullable();

            $table->text('notes')->nullable();
            $table->unsignedSmallInteger('sequence_no')->default(1);
            $table->timestamps();

            $table->index(['outside_labour_credit_note_id', 'sequence_no'], 'olcn_items_note_sequence_index');
        });

        Schema::create('outside_labour_credit_note_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('outside_labour_credit_note_id')->constrained()->cascadeOnDelete();
            $table->string('attachment_type', 20)->nullable(); // vendor_bill_copy / warranty_card
            $table->string('kind', 10)->default('image');
            $table->string('path');
            $table->string('original_name')->nullable();
            $table->unsignedInteger('size_bytes')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedSmallInteger('sequence_no')->default(1);
            $table->timestamps();

            $table->index(['outside_labour_credit_note_id', 'sequence_no'], 'olcn_attachments_note_sequence_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('outside_labour_credit_note_attachments');
        Schema::dropIfExists('outside_labour_credit_note_items');
        Schema::dropIfExists('outside_labour_credit_notes');
    }
};
