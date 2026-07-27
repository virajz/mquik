<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Module 21 — Surveyor Inspection.
 *
 * Record an insurance surveyor's findings against a claim/estimate: survey
 * type, per-line repair/replace decisions, overall approval, and any
 * not-covered / rejection reasons. Surveyors are external insurer staff, so
 * they're captured as name + phone rather than an employee record.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('surveyor_inspections', function (Blueprint $table) {
            $table->id();
            $table->string('inspection_no')->nullable()->unique();

            $table->foreignId('job_card_id')->nullable()->constrained('job_cards')->nullOnDelete();
            $table->foreignId('claim_intimation_id')->nullable()->constrained('claim_intimations')->nullOnDelete();
            $table->foreignId('sales_estimate_id')->nullable()->constrained('sales_estimates')->nullOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->foreignId('customer_vehicle_id')->nullable()->constrained('customer_vehicles')->nullOnDelete();
            $table->foreignId('insurance_company_id')->nullable()->constrained('insurance_companies')->nullOnDelete();

            $table->string('surveyor_name')->nullable();
            $table->string('surveyor_phone', 20)->nullable();

            $table->string('survey_type', 20)->nullable();       // preliminary / re_inspection / final / spot / supplementary
            $table->string('surveyor_approval', 20)->nullable();  // repair / replace / not_approved / partial / total_loss
            $table->string('not_covered_reason', 30)->nullable(); // old_damage / damage_mismatch / not_in_policy
            $table->string('rejection_reason', 30)->nullable();   // document_missing / policy_expired / policy_invalid / fraud / insufficient_evidence
            $table->string('status', 20)->default('pending');     // pending / in_progress / completed / cancelled

            $table->dateTime('surveyed_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('job_card_id');
            $table->index(['insurance_company_id', 'status']);
        });

        Schema::create('surveyor_inspection_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('surveyor_inspection_id')->constrained()->cascadeOnDelete();
            $table->string('line_type', 10); // spare | labour
            $table->foreignId('spare_id')->nullable()->constrained('spares')->nullOnDelete();
            $table->foreignId('labour_id')->nullable()->constrained('labours')->nullOnDelete();
            $table->string('description');
            $table->decimal('quantity', 12, 2)->default(1);
            $table->string('line_approval', 20)->nullable(); // repair / replace / remove_refit / approved / rejected / pending
            $table->unsignedSmallInteger('sequence_no')->default(1);
            $table->timestamps();

            $table->index(['surveyor_inspection_id', 'sequence_no'], 'si_items_inspection_sequence_index');
        });

        Schema::create('surveyor_inspection_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('surveyor_inspection_id')->constrained()->cascadeOnDelete();
            $table->string('attachment_type', 20)->nullable(); // approval_note / survey_photo / other
            $table->string('kind', 10)->default('image'); // image / pdf
            $table->string('path');
            $table->string('original_name')->nullable();
            $table->unsignedInteger('size_bytes')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedSmallInteger('sequence_no')->default(1);
            $table->timestamps();

            $table->index(['surveyor_inspection_id', 'sequence_no'], 'si_attachments_inspection_sequence_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('surveyor_inspection_attachments');
        Schema::dropIfExists('surveyor_inspection_items');
        Schema::dropIfExists('surveyor_inspections');
    }
};
