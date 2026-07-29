<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Module 75 — Sales Inquiry (Parts / Service) Entry.
 *
 * Capture a customer inquiry for service / parts from any channel (walk-in,
 * phone, WhatsApp, web, app), assign it, follow it up and track it through to
 * conversion or loss. Also covers module 76 (Sales Inquiry Follow-Ups) — the
 * follow-up attempt / escalation / status live on this record. Header +
 * inquiry-evidence attachments (VIN / vehicle photos / voice note).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_inquiries', function (Blueprint $table) {
            $table->id();
            $table->string('inquiry_no')->nullable()->unique();

            $table->string('inquiry_type', 30)->nullable();
            $table->string('inquiry_source', 20)->nullable();
            $table->string('priority', 10)->default('normal'); // normal / medium / high
            $table->string('status', 20)->default('pending');

            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->foreignId('customer_vehicle_id')->nullable()->constrained('customer_vehicles')->nullOnDelete();
            $table->foreignId('workshop_department_id')->nullable()->constrained('workshop_departments')->nullOnDelete();
            $table->foreignId('assigned_by_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->foreignId('assigned_to_id')->nullable()->constrained('employees')->nullOnDelete();

            $table->string('follow_up_attempt', 10)->nullable(); // first / second / third / final
            $table->string('escalation', 20)->nullable();
            $table->string('escalation_reason', 20)->nullable();
            $table->string('lost_reason', 30)->nullable();

            $table->text('inquiry_details')->nullable();
            $table->decimal('estimated_value', 12, 2)->nullable(); // potential revenue

            $table->dateTime('inquiry_at')->nullable();
            $table->dateTime('assigned_at')->nullable();
            $table->dateTime('quotation_at')->nullable();
            $table->dateTime('follow_up_at')->nullable();
            $table->dateTime('converted_at')->nullable();
            $table->dateTime('closed_at')->nullable();

            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('inquiry_source');
            $table->index('created_at');
        });

        Schema::create('sales_inquiry_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sales_inquiry_id')->constrained()->cascadeOnDelete();
            $table->string('attachment_type', 20)->nullable(); // vin_photo / vehicle_photo / voice_recording
            $table->string('kind', 10)->default('image');
            $table->string('path');
            $table->string('original_name')->nullable();
            $table->unsignedInteger('size_bytes')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedSmallInteger('sequence_no')->default(1);
            $table->timestamps();

            $table->index(['sales_inquiry_id', 'sequence_no'], 'sinq_attachments_inquiry_sequence_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_inquiry_attachments');
        Schema::dropIfExists('sales_inquiries');
    }
};
