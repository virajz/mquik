<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_returns', function (Blueprint $table) {
            $table->id();
            $table->string('return_no', 32)->nullable()->unique();  // MQ/SR|IR|CR/25-26/00026
            $table->string('fy_label', 7)->nullable();              // 25-26
            $table->string('return_type', 20)->default('regular');  // regular | insurance | counter
            $table->string('reference_mode', 20)->default('item_wise'); // whole_bill | item_wise
            $table->string('status', 30)->default('draft');         // draft | finalized | cancelled
            $table->string('refund_status', 20)->default('unpaid'); // unpaid | fully_refunded | partially_refunded
            $table->string('discount_type', 20)->nullable();
            $table->decimal('discount_value', 12, 2)->default(0);

            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->foreignId('customer_vehicle_id')->nullable()->constrained('customer_vehicles')->nullOnDelete();
            $table->foreignId('regular_sales_invoice_id')->nullable()->constrained('regular_sales_invoices')->nullOnDelete();
            $table->foreignId('counter_sales_invoice_id')->nullable()->constrained('counter_sales_invoices')->nullOnDelete();
            $table->foreignId('insurance_company_id')->nullable()->constrained('insurance_companies')->nullOnDelete();
            $table->foreignId('service_package_id')->nullable()->constrained('service_packages')->nullOnDelete();
            $table->foreignId('amc_package_id')->nullable()->constrained('service_packages')->nullOnDelete();
            $table->foreignId('department_id')->nullable()->constrained('workshop_departments')->nullOnDelete();
            $table->foreignId('advisor_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->foreignId('technician_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->foreignId('vendor_id')->nullable()->constrained('vendors')->nullOnDelete();
            $table->foreignId('sales_return_reason_id')->nullable()->constrained('sales_return_reasons')->nullOnDelete();
            $table->foreignId('cancellation_reason_id')->nullable()->constrained('invoice_cancellation_reasons')->nullOnDelete();

            $table->decimal('parts_total', 14, 2)->default(0);
            $table->decimal('labour_total', 14, 2)->default(0);
            $table->decimal('discount_total', 14, 2)->default(0);
            $table->decimal('tax_total', 14, 2)->default(0);
            $table->decimal('grand_total', 14, 2)->default(0);
            $table->decimal('refunded_amount', 14, 2)->default(0);
            $table->decimal('balance_refund', 14, 2)->default(0);
            $table->text('notes')->nullable();
            $table->dateTime('returned_at')->nullable();
            $table->dateTime('refunded_at')->nullable();
            $table->dateTime('cancelled_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index(['customer_id', 'status']);
            $table->index(['refund_status']);
            $table->index(['return_type', 'fy_label']);
            $table->index(['regular_sales_invoice_id']);
            $table->index(['counter_sales_invoice_id']);
        });

        Schema::create('sales_return_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sales_return_id')->constrained('sales_returns')->cascadeOnDelete();
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

            $table->index(['sales_return_id', 'sequence_no']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_return_items');
        Schema::dropIfExists('sales_returns');
    }
};
