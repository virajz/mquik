<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_collections', function (Blueprint $table) {
            $table->id();
            $table->string('doc_collection_no', 32)->nullable()->unique();

            // Context / links
            $table->foreignId('job_card_id')->nullable()->constrained('job_cards')->nullOnDelete();
            $table->foreignId('customer_id')->constrained('customers')->restrictOnDelete();
            $table->foreignId('customer_vehicle_id')->constrained('customer_vehicles')->restrictOnDelete();
            $table->foreignId('department_id')->nullable()->constrained('workshop_departments')->nullOnDelete();
            $table->foreignId('service_type_id')->nullable()->constrained('service_types')->nullOnDelete();
            $table->foreignId('created_by_advisor_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->foreignId('collected_by_driver_id')->nullable()->constrained('employees')->nullOnDelete();

            // Insurance
            $table->foreignId('insurance_company_id')->nullable()->constrained('insurance_companies')->nullOnDelete();
            $table->foreignId('insurance_policy_type_id')->nullable()->constrained('insurance_policy_types')->nullOnDelete();
            $table->foreignId('claim_type_id')->nullable()->constrained('claim_types')->nullOnDelete();
            $table->string('policy_no', 60)->nullable();

            // Checklists (reuse the generic checklist framework)
            $table->foreignId('checklist_template_id')->nullable()->constrained('checklist_templates')->nullOnDelete();
            $table->foreignId('verification_template_id')->nullable()->constrained('checklist_templates')->nullOnDelete();

            // Classification (small fixed lists — enums, not masters)
            $table->string('request_type', 20)->default('customer');   // insurance_claim | customer
            $table->string('purpose', 40)->nullable();                 // insurance_process | ownership_confirmation | payment_limit | repair_authorization
            $table->string('status', 20)->default('pending');          // pending | requested | received | rejected | cancelled
            $table->string('reminder_frequency', 20)->nullable();      // daily | every_2_days | custom
            $table->string('retention', 20)->default('active');        // active | archive | delete

            // Outcome reasons
            $table->foreignId('missing_document_reason_id')->nullable()->constrained('missing_document_reasons')->nullOnDelete();
            $table->foreignId('rejection_reason_id')->nullable()->constrained('document_rejection_reasons')->nullOnDelete();
            $table->foreignId('follow_up_mode_id')->nullable()->constrained('follow_up_modes')->nullOnDelete();

            // Lifecycle timestamps
            $table->dateTime('requested_at')->nullable();
            $table->dateTime('received_at')->nullable();
            $table->dateTime('entry_at')->nullable();
            $table->dateTime('uploaded_at')->nullable();

            $table->string('customer_signature_path')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index('job_card_id');
            $table->index('customer_vehicle_id');
            $table->index(['request_type', 'status']);
        });

        Schema::create('document_collection_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_collection_id')->constrained('document_collections')->cascadeOnDelete();
            $table->string('label');                                   // snapshot of the checklist item label
            $table->boolean('is_required')->default(false);
            $table->string('status', 20)->default('pending');          // pending | received | rejected
            $table->foreignId('rejection_reason_id')->nullable()->constrained('document_rejection_reasons')->nullOnDelete();
            $table->string('path')->nullable();
            $table->string('original_name')->nullable();
            $table->string('mime_type', 80)->nullable();
            $table->unsignedInteger('size_bytes')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedSmallInteger('sequence_no')->default(1);
            $table->timestamps();

            $table->index(['document_collection_id', 'sequence_no']);
        });

        Schema::create('document_collection_verifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_collection_id')->constrained('document_collections')->cascadeOnDelete();
            $table->string('label');
            $table->boolean('is_verified')->default(false);
            $table->text('notes')->nullable();
            $table->unsignedSmallInteger('sequence_no')->default(1);
            $table->timestamps();

            $table->index(['document_collection_id', 'sequence_no']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_collection_verifications');
        Schema::dropIfExists('document_collection_items');
        Schema::dropIfExists('document_collections');
    }
};
