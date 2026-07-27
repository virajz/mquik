<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Module 12 — OLI (Outside Labour Inquiry).
 *
 * A request-for-quote to an outside contractor/vendor for work the workshop
 * doesn't do in-house (denting, painting, upholstery, etc). Tracks the inquiry
 * lifecycle (sent → responded → work order issued / rejected), the promised
 * turnaround, follow-up config (no messages are sent yet — config only), and
 * evidence/attachments.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('outside_labour_inquiries', function (Blueprint $table) {
            $table->id();
            // Stamped in the model's created() hook (needs the id), so it must
            // allow NULL for the initial insert.
            $table->string('inquiry_no')->nullable()->unique();

            // What kind of outside work — reuses the vendor "service speciality" catalogue.
            $table->foreignId('inquiry_type_id')->nullable()->constrained('service_specialists')->nullOnDelete();
            $table->foreignId('workshop_department_id')->nullable()->constrained('workshop_departments')->nullOnDelete();
            $table->foreignId('employee_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->foreignId('job_card_id')->nullable()->constrained('job_cards')->nullOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->foreignId('customer_vehicle_id')->nullable()->constrained('customer_vehicles')->nullOnDelete();
            $table->foreignId('vendor_id')->nullable()->constrained('vendors')->nullOnDelete();

            // Billing classification for the outside labour.
            $table->foreignId('hsn_id')->nullable()->constrained('hsn_codes')->nullOnDelete();
            $table->foreignId('tax_id')->nullable()->constrained('taxes')->nullOnDelete();

            $table->foreignId('priority_id')->nullable()->constrained('priorities')->nullOnDelete();

            // How the inquiry was sent.
            $table->string('communication_mode', 20)->nullable(); // whatsapp / email / phone

            // Turnaround: a preset (one/two/three days) or a custom number of days.
            $table->string('tat_option', 20)->nullable(); // one_day / two_days / three_days / custom
            $table->unsignedSmallInteger('tat_custom_days')->nullable();
            $table->dateTime('promised_from')->nullable();
            $table->dateTime('promised_to')->nullable();

            $table->foreignId('revision_reason_id')->nullable()->constrained('estimate_revision_reasons')->nullOnDelete();
            $table->foreignId('rejection_reason_id')->nullable()->constrained('outside_labour_rejection_reasons')->nullOnDelete();

            $table->string('status', 30)->default('response_pending');

            // Follow-up / reminder configuration — NOT sending anything yet, config only.
            $table->string('reminder_frequency', 20)->nullable(); // daily / every_2_days / custom
            $table->unsignedSmallInteger('reminder_custom_days')->nullable();
            $table->foreignId('follow_up_mode_id')->nullable()->constrained('follow_up_modes')->nullOnDelete();
            $table->string('notification_stage', 30)->nullable(); // inquiry_sent / response_received / approved

            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index(['vendor_id', 'status']);
            $table->index('job_card_id');
        });

        Schema::create('outside_labour_inquiry_scopes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('outside_labour_inquiry_id')->constrained()->cascadeOnDelete();
            $table->foreignId('labour_id')->nullable()->constrained('labours')->nullOnDelete();
            $table->foreignId('job_description_id')->nullable()->constrained('job_descriptions')->nullOnDelete();
            $table->foreignId('complaint_type_id')->nullable()->constrained('complaint_types')->nullOnDelete();
            $table->text('description');
            $table->unsignedSmallInteger('sequence_no')->default(1);
            $table->timestamps();

            $table->index(['outside_labour_inquiry_id', 'sequence_no'], 'oli_scopes_inquiry_sequence_index');
        });

        Schema::create('outside_labour_inquiry_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('outside_labour_inquiry_id')->constrained()->cascadeOnDelete();
            // A typed evidence photo (Before/After/Front/Rear/Damage/Fault) OR a plain document.
            $table->foreignId('photo_type_id')->nullable()->constrained('photo_types')->nullOnDelete();
            $table->string('kind', 10)->default('image'); // image / pdf
            $table->string('path');
            $table->string('original_name')->nullable();
            $table->unsignedInteger('size_bytes')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedSmallInteger('sequence_no')->default(1);
            $table->timestamps();

            $table->index(['outside_labour_inquiry_id', 'sequence_no'], 'oli_attachments_inquiry_sequence_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('outside_labour_inquiry_attachments');
        Schema::dropIfExists('outside_labour_inquiry_scopes');
        Schema::dropIfExists('outside_labour_inquiries');
    }
};
