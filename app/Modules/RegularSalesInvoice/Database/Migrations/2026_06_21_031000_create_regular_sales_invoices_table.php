<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('regular_sales_invoices', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_no', 32)->nullable()->unique();  // MQ/25-26/00001
            $table->string('fy_label', 7)->nullable();               // 25-26
            $table->string('invoice_type', 20)->default('regular');  // regular | insurance
            $table->string('status', 30)->default('draft');          // draft | finalized | cancelled | credit_note
            $table->string('payment_status', 20)->default('unpaid'); // unpaid | partially_paid | fully_paid | refunded
            $table->string('warranty_type', 30)->nullable();         // no_warranty | self | vendor | manufacturer
            $table->string('warranty_period', 20)->nullable();       // 3m | 6m | 12m | 24m
            $table->string('discount_type', 20)->nullable();         // line | scheme | cash
            $table->decimal('discount_value', 12, 2)->default(0);

            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->foreignId('customer_vehicle_id')->constrained('customer_vehicles')->cascadeOnDelete();
            $table->foreignId('job_card_id')->nullable()->constrained('job_cards')->nullOnDelete();
            $table->foreignId('sales_estimate_id')->nullable()->constrained('sales_estimates')->nullOnDelete();
            $table->foreignId('proforma_id')->nullable()->constrained('proformas')->nullOnDelete();
            $table->foreignId('department_id')->nullable()->constrained('workshop_departments')->nullOnDelete();
            $table->foreignId('service_type_id')->nullable()->constrained('service_types')->nullOnDelete();
            $table->foreignId('insurance_company_id')->nullable()->constrained('insurance_companies')->nullOnDelete();
            $table->foreignId('service_package_id')->nullable()->constrained('service_packages')->nullOnDelete();
            $table->foreignId('amc_package_id')->nullable()->constrained('service_packages')->nullOnDelete();
            $table->foreignId('loss_type_id')->nullable()->constrained('loss_types')->nullOnDelete();
            $table->foreignId('loss_reason_id')->nullable()->constrained('loss_reasons')->nullOnDelete();
            $table->foreignId('payment_mode_id')->nullable()->constrained('payment_modes')->nullOnDelete();
            $table->foreignId('cancellation_reason_id')->nullable()->constrained('invoice_cancellation_reasons')->nullOnDelete();
            $table->foreignId('advisor_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->foreignId('technician_id')->nullable()->constrained('employees')->nullOnDelete();
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
            $table->decimal('amount_paid', 14, 2)->default(0);
            $table->decimal('balance_due', 14, 2)->default(0);
            $table->text('notes')->nullable();
            $table->dateTime('invoiced_at')->nullable();
            $table->dateTime('paid_at')->nullable();
            $table->dateTime('cancelled_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index(['customer_vehicle_id', 'status']);
            $table->index(['payment_status']);
            $table->index(['fy_label']);
            $table->index(['job_card_id']);
        });

        Schema::create('regular_sales_invoice_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('regular_sales_invoice_id')->constrained('regular_sales_invoices')->cascadeOnDelete();
            $table->string('line_type', 10);  // spare | labour
            $table->foreignId('spare_id')->nullable()->constrained('spares')->nullOnDelete();
            $table->foreignId('labour_id')->nullable()->constrained('labours')->nullOnDelete();
            $table->foreignId('uom_id')->nullable()->constrained('units_of_measure')->nullOnDelete();
            $table->foreignId('tax_id')->nullable()->constrained('taxes')->nullOnDelete();
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

            $table->index(['regular_sales_invoice_id', 'sequence_no']);
        });

        Schema::create('regular_sales_invoice_deductions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('regular_sales_invoice_id')->constrained('regular_sales_invoices')->cascadeOnDelete();
            $table->foreignId('insurance_deduction_type_id')->nullable()->constrained('insurance_deduction_types')->nullOnDelete();
            $table->decimal('amount', 14, 2)->default(0);
            $table->string('notes')->nullable();
            $table->unsignedInteger('sequence_no')->default(0);
            $table->timestamps();
        });

        Schema::create('regular_sales_invoice_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('regular_sales_invoice_id')->constrained('regular_sales_invoices')->cascadeOnDelete();
            $table->string('path');
            $table->string('original_name')->nullable();
            $table->string('mime_type', 100)->nullable();
            $table->unsignedBigInteger('size_bytes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('regular_sales_invoice_attachments');
        Schema::dropIfExists('regular_sales_invoice_deductions');
        Schema::dropIfExists('regular_sales_invoice_items');
        Schema::dropIfExists('regular_sales_invoices');
    }
};
