<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Module 71 — Customer Complaints.
 *
 * Register and track the resolution of a customer (or internal) complaint —
 * type, source, assignment, root cause, resolution, satisfaction scores and the
 * investigation → resolved / rejected / reopened lifecycle with per-stage
 * timestamps. Header + document / media attachments.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_complaints', function (Blueprint $table) {
            $table->id();
            $table->string('complaint_no')->nullable()->unique();

            $table->string('complaint_type', 30)->nullable();
            $table->string('complaint_source', 20)->nullable();
            $table->string('priority', 10)->default('normal'); // normal / medium / high
            $table->string('assignment', 20)->nullable();
            $table->string('root_cause', 30)->nullable();
            $table->string('resolution_type', 30)->nullable();
            $table->string('status', 30)->default('under_investigation');
            $table->string('reopen_reason', 30)->nullable();

            $table->foreignId('workshop_department_id')->nullable()->constrained('workshop_departments')->nullOnDelete();
            $table->foreignId('service_type_id')->nullable()->constrained('service_types')->nullOnDelete();
            $table->foreignId('opened_by_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->foreignId('advisor_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->foreignId('technician_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->foreignId('job_card_id')->nullable()->constrained('job_cards')->nullOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->foreignId('customer_vehicle_id')->nullable()->constrained('customer_vehicles')->nullOnDelete();

            $table->string('invoice_reference')->nullable();
            $table->text('description')->nullable();

            $table->unsignedTinyInteger('achieved_score')->nullable();     // 1-5
            $table->unsignedTinyInteger('recommended_score')->nullable();  // 1-5

            $table->dateTime('opened_at')->nullable();
            $table->dateTime('assigned_at')->nullable();
            $table->dateTime('investigation_start_at')->nullable();
            $table->dateTime('investigation_complete_at')->nullable();
            $table->dateTime('closed_at')->nullable();
            $table->dateTime('reopened_at')->nullable();

            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('complaint_type');
            $table->index('created_at');
        });

        Schema::create('customer_complaint_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_complaint_id')->constrained()->cascadeOnDelete();
            $table->string('attachment_type', 30)->nullable(); // complaint_copy / complaint_video / voice_recording / investigation_report / resolution_copy
            $table->string('kind', 10)->default('image'); // image / pdf / video / audio
            $table->string('path');
            $table->string('original_name')->nullable();
            $table->unsignedInteger('size_bytes')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedSmallInteger('sequence_no')->default(1);
            $table->timestamps();

            $table->index(['customer_complaint_id', 'sequence_no'], 'cc_attachments_complaint_sequence_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_complaint_attachments');
        Schema::dropIfExists('customer_complaints');
    }
};
