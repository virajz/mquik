<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_entries', function (Blueprint $table) {
            $table->id();
            $table->string('purchase_no', 32)->nullable()->unique();
            $table->string('invoice_type', 30)->default('tax_invoice');  // tax_invoice | e_invoice | retail_invoice | bill_of_supply
            $table->string('invoice_no', 60)->nullable();
            $table->date('invoice_date')->nullable();
            $table->string('purchase_type', 30)->default('stock');
            $table->string('discount_scheme', 20)->nullable();
            $table->string('inventory_status', 30)->default('fully_received');
            $table->foreignId('vendor_id')->constrained('vendors')->cascadeOnDelete();
            $table->foreignId('challan_id')->nullable()->constrained('challans')->nullOnDelete();
            $table->foreignId('transport_mode_id')->nullable()->constrained('transport_modes')->nullOnDelete();
            $table->foreignId('transport_company_id')->nullable()->constrained('courier_companies')->nullOnDelete();
            $table->foreignId('challan_reason_id')->nullable()->constrained('challan_reasons')->nullOnDelete();
            $table->foreignId('job_card_id')->nullable()->constrained('job_cards')->nullOnDelete();
            $table->foreignId('department_id')->nullable()->constrained('workshop_departments')->nullOnDelete();
            $table->foreignId('advisor_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->foreignId('technician_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->foreignId('store_incharge_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->foreignId('driver_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->string('po_reference', 60)->nullable();
            $table->decimal('parts_total', 14, 2)->default(0);
            $table->decimal('discount_total', 14, 2)->default(0);
            $table->decimal('tax_total', 14, 2)->default(0);
            $table->decimal('charges_total', 14, 2)->default(0);
            $table->decimal('grand_total', 14, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['vendor_id', 'created_at']);
            $table->index(['inventory_status', 'created_at']);
            $table->index(['job_card_id']);
        });

        Schema::create('purchase_entry_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_entry_id')->constrained('purchase_entries')->cascadeOnDelete();
            $table->foreignId('spare_id')->nullable()->constrained('spares')->nullOnDelete();
            $table->foreignId('uom_id')->nullable()->constrained('units_of_measure')->nullOnDelete();
            $table->foreignId('tax_id')->nullable()->constrained('taxes')->nullOnDelete();
            $table->foreignId('rejection_reason_id')->nullable()->constrained('challan_rejection_reasons')->nullOnDelete();
            $table->string('description');
            $table->string('hsn_code', 16)->nullable();
            $table->decimal('qty', 10, 2)->default(1);
            $table->decimal('unit_rate', 14, 2)->default(0);
            $table->decimal('discount_value', 14, 2)->default(0);
            $table->decimal('tax_percent', 5, 2)->default(0);
            $table->decimal('line_total', 14, 2)->default(0);
            $table->string('material_condition', 20)->default('new');
            $table->string('invoice_status', 20)->default('received');
            $table->unsignedInteger('sequence_no')->default(0);
            $table->timestamps();

            $table->index(['purchase_entry_id', 'sequence_no']);
        });

        Schema::create('purchase_entry_charges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_entry_id')->constrained('purchase_entries')->cascadeOnDelete();
            $table->foreignId('charge_type_id')->nullable()->constrained('charge_types')->nullOnDelete();
            $table->decimal('amount', 14, 2)->default(0);
            $table->string('notes')->nullable();
            $table->unsignedInteger('sequence_no')->default(0);
            $table->timestamps();
        });

        Schema::create('purchase_entry_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_entry_id')->constrained('purchase_entries')->cascadeOnDelete();
            $table->string('path');
            $table->string('original_name')->nullable();
            $table->string('mime_type', 100)->nullable();
            $table->unsignedBigInteger('size_bytes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_entry_attachments');
        Schema::dropIfExists('purchase_entry_charges');
        Schema::dropIfExists('purchase_entry_items');
        Schema::dropIfExists('purchase_entries');
    }
};
