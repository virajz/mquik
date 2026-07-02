<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('proformas', function (Blueprint $table) {
            $table->id();
            $table->string('proforma_no', 32)->nullable()->unique();
            $table->string('status', 30)->default('draft');
            $table->string('warranty_type', 30)->nullable();      // no_warranty | self | vendor | manufacturer
            $table->string('warranty_period', 20)->nullable();    // 3m | 6m | 12m | 24m
            $table->string('discount_type', 20)->nullable();      // line | scheme | cash
            $table->decimal('discount_value', 12, 2)->default(0);
            $table->string('approval_authority', 30)->nullable();
            $table->string('approval_status', 20)->default('pending');
            $table->string('communication_mode', 20)->nullable(); // whatsapp | email | physical_signature

            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->foreignId('customer_vehicle_id')->constrained('customer_vehicles')->cascadeOnDelete();
            $table->foreignId('job_card_id')->nullable()->constrained('job_cards')->nullOnDelete();
            $table->foreignId('sales_estimate_id')->nullable()->constrained('sales_estimates')->nullOnDelete();
            $table->foreignId('department_id')->nullable()->constrained('workshop_departments')->nullOnDelete();
            $table->foreignId('service_type_id')->nullable()->constrained('service_types')->nullOnDelete();
            $table->foreignId('insurance_company_id')->nullable()->constrained('insurance_companies')->nullOnDelete();
            $table->foreignId('service_package_id')->nullable()->constrained('service_packages')->nullOnDelete();
            $table->foreignId('loss_type_id')->nullable()->constrained('loss_types')->nullOnDelete();
            $table->foreignId('loss_reason_id')->nullable()->constrained('loss_reasons')->nullOnDelete();
            $table->foreignId('revision_reason_id')->nullable()->constrained('estimate_revision_reasons')->nullOnDelete();
            $table->foreignId('advisor_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->foreignId('technician_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->foreignId('store_incharge_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->foreignId('vendor_id')->nullable()->constrained('vendors')->nullOnDelete();

            $table->string('policy_no', 60)->nullable();
            $table->text('recommended_service')->nullable();
            $table->decimal('parts_total', 14, 2)->default(0);
            $table->decimal('labour_total', 14, 2)->default(0);
            $table->decimal('discount_total', 14, 2)->default(0);
            $table->decimal('tax_total', 14, 2)->default(0);
            $table->decimal('grand_total', 14, 2)->default(0);
            $table->decimal('cost_total', 14, 2)->default(0);
            $table->decimal('profit_total', 14, 2)->default(0);
            $table->decimal('margin_percent', 6, 2)->default(0);
            $table->decimal('deductions_total', 14, 2)->default(0);
            $table->text('notes')->nullable();
            $table->dateTime('prepared_at')->nullable();
            $table->dateTime('sent_at')->nullable();
            $table->dateTime('approved_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index(['customer_vehicle_id', 'status']);
            $table->index(['job_card_id']);
        });

        Schema::create('proforma_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proforma_id')->constrained('proformas')->cascadeOnDelete();
            $table->string('line_type', 10);  // spare | labour
            $table->foreignId('spare_id')->nullable()->constrained('spares')->nullOnDelete();
            $table->foreignId('labour_id')->nullable()->constrained('labours')->nullOnDelete();
            $table->foreignId('tax_id')->nullable()->constrained('taxes')->nullOnDelete();
            $table->foreignId('rejection_reason_id')->nullable()->constrained('challan_rejection_reasons')->nullOnDelete();
            $table->string('description');
            $table->string('hsn_code', 16)->nullable();
            $table->decimal('qty', 10, 2)->default(1);
            $table->decimal('cost_rate', 14, 2)->default(0);
            $table->decimal('unit_rate', 14, 2)->default(0);
            $table->decimal('discount_value', 14, 2)->default(0);
            $table->decimal('tax_percent', 5, 2)->default(0);
            $table->decimal('line_total', 14, 2)->default(0);
            $table->unsignedInteger('sequence_no')->default(0);
            $table->timestamps();

            $table->index(['proforma_id', 'sequence_no']);
        });

        Schema::create('proforma_deductions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proforma_id')->constrained('proformas')->cascadeOnDelete();
            $table->foreignId('insurance_deduction_type_id')->nullable()->constrained('insurance_deduction_types')->nullOnDelete();
            $table->decimal('amount', 14, 2)->default(0);
            $table->string('notes')->nullable();
            $table->unsignedInteger('sequence_no')->default(0);
            $table->timestamps();
        });

        Schema::create('proforma_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proforma_id')->constrained('proformas')->cascadeOnDelete();
            $table->string('path');
            $table->string('original_name')->nullable();
            $table->string('mime_type', 100)->nullable();
            $table->unsignedBigInteger('size_bytes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('proforma_attachments');
        Schema::dropIfExists('proforma_deductions');
        Schema::dropIfExists('proforma_items');
        Schema::dropIfExists('proformas');
    }
};
