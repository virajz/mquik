<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Module 23 — Sales Estimate Approval.
 *
 * Customer / advisor / insurer approval of a sales estimate, line-by-line
 * (repair / replace / R&R / approved / rejected), with depreciation by part
 * category, reminder/follow-up config, and an 8-state approval lifecycle.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_estimate_approvals', function (Blueprint $table) {
            $table->id();
            $table->string('approval_no')->nullable()->unique();

            $table->foreignId('job_card_id')->nullable()->constrained('job_cards')->nullOnDelete();
            $table->foreignId('sales_estimate_id')->nullable()->constrained('sales_estimates')->nullOnDelete();
            $table->foreignId('surveyor_inspection_id')->nullable()->constrained('surveyor_inspections')->nullOnDelete();
            $table->foreignId('insurance_company_id')->nullable()->constrained('insurance_companies')->nullOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->foreignId('customer_vehicle_id')->nullable()->constrained('customer_vehicles')->nullOnDelete();
            $table->foreignId('workshop_department_id')->nullable()->constrained('workshop_departments')->nullOnDelete();
            $table->foreignId('service_type_id')->nullable()->constrained('service_types')->nullOnDelete();
            $table->foreignId('employee_id')->nullable()->constrained('employees')->nullOnDelete(); // advisor
            $table->foreignId('approval_mode_id')->nullable()->constrained('customer_approval_types')->nullOnDelete();
            $table->foreignId('follow_up_mode_id')->nullable()->constrained('follow_up_modes')->nullOnDelete();

            $table->string('approval_type', 20)->nullable();        // regular / supplementary / additional / revised
            $table->string('approval_authorisation', 20)->nullable(); // insurance / customer / both / internal / management
            $table->string('parts_brand_preference', 20)->nullable(); // genuine / aftermarket / any
            $table->string('rejection_reason', 30)->nullable();
            $table->string('status', 30)->default('sent_for_approval');

            $table->string('reminder_frequency', 20)->nullable();  // daily / every_2_days / custom
            $table->unsignedSmallInteger('reminder_custom_days')->nullable();

            $table->dateTime('customer_approved_at')->nullable();
            $table->dateTime('insurance_approved_at')->nullable();
            $table->dateTime('approved_at')->nullable(); // stamped when fully approved (drives the KPI)

            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('approved_at');
            $table->index('job_card_id');
        });

        Schema::create('sales_estimate_approval_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sales_estimate_approval_id')->constrained()->cascadeOnDelete();
            $table->string('line_type', 10); // spare | labour | package
            $table->foreignId('spare_id')->nullable()->constrained('spares')->nullOnDelete();
            $table->foreignId('labour_id')->nullable()->constrained('labours')->nullOnDelete();
            $table->foreignId('service_package_id')->nullable()->constrained('service_packages')->nullOnDelete();
            $table->foreignId('inventory_group_id')->nullable()->constrained('inventory_groups')->nullOnDelete();
            $table->foreignId('hsn_id')->nullable()->constrained('hsn_codes')->nullOnDelete();
            $table->foreignId('tax_id')->nullable()->constrained('taxes')->nullOnDelete();

            $table->string('description');
            $table->decimal('quantity', 12, 2)->default(1);
            $table->decimal('unit_rate', 12, 2)->nullable();
            $table->string('line_approval', 20)->nullable();       // repair / replace / remove_refit / approved / rejected / pending
            $table->string('depreciation_category', 15)->nullable(); // plastic / metal / rubber / glass
            $table->decimal('depreciation_percent', 5, 2)->nullable();

            $table->unsignedSmallInteger('sequence_no')->default(1);
            $table->timestamps();

            $table->index(['sales_estimate_approval_id', 'sequence_no'], 'sea_items_approval_sequence_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_estimate_approval_items');
        Schema::dropIfExists('sales_estimate_approvals');
    }
};
