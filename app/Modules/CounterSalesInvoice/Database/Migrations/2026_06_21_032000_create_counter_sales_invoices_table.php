<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('counter_sales_invoices', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_no', 32)->nullable()->unique();  // MQ/CS/25-26/00001
            $table->string('fy_label', 7)->nullable();               // 25-26
            $table->string('status', 30)->default('draft');          // draft | finalized | cancelled | credit_note
            $table->string('payment_status', 20)->default('unpaid'); // unpaid | partially_paid | fully_paid | refunded
            $table->string('delivery_type', 20)->default('counter_pickup'); // counter_pickup | home_delivery | courier | porter | liftor
            $table->string('warranty_type', 30)->nullable();
            $table->string('warranty_period', 20)->nullable();
            $table->string('discount_type', 20)->nullable();
            $table->decimal('discount_value', 12, 2)->default(0);

            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->foreignId('courier_company_id')->nullable()->constrained('courier_companies')->nullOnDelete();
            $table->foreignId('transport_mode_id')->nullable()->constrained('transport_modes')->nullOnDelete();
            $table->foreignId('department_id')->nullable()->constrained('workshop_departments')->nullOnDelete();
            $table->foreignId('loss_type_id')->nullable()->constrained('loss_types')->nullOnDelete();
            $table->foreignId('payment_mode_id')->nullable()->constrained('payment_modes')->nullOnDelete();
            $table->foreignId('cancellation_reason_id')->nullable()->constrained('invoice_cancellation_reasons')->nullOnDelete();
            $table->foreignId('advisor_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->foreignId('technician_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->foreignId('vendor_id')->nullable()->constrained('vendors')->nullOnDelete();

            $table->string('tracking_no', 60)->nullable();
            $table->text('delivery_address')->nullable();
            $table->text('recommended_service')->nullable();
            $table->decimal('parts_total', 14, 2)->default(0);
            $table->decimal('labour_total', 14, 2)->default(0);
            $table->decimal('discount_total', 14, 2)->default(0);
            $table->decimal('tax_total', 14, 2)->default(0);
            $table->decimal('grand_total', 14, 2)->default(0);
            $table->decimal('cost_total', 14, 2)->default(0);
            $table->decimal('profit_total', 14, 2)->default(0);
            $table->decimal('margin_percent', 6, 2)->default(0);
            $table->decimal('amount_paid', 14, 2)->default(0);
            $table->decimal('balance_due', 14, 2)->default(0);
            $table->text('notes')->nullable();
            $table->dateTime('invoiced_at')->nullable();
            $table->dateTime('paid_at')->nullable();
            $table->dateTime('cancelled_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index(['customer_id', 'status']);
            $table->index(['payment_status']);
            $table->index(['fy_label']);
        });

        Schema::create('counter_sales_invoice_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('counter_sales_invoice_id')->constrained('counter_sales_invoices')->cascadeOnDelete();
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

            $table->index(['counter_sales_invoice_id', 'sequence_no']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('counter_sales_invoice_items');
        Schema::dropIfExists('counter_sales_invoices');
    }
};
