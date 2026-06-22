<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('challans', function (Blueprint $table) {
            $table->id();
            $table->string('challan_no', 32)->nullable()->unique();
            $table->string('purchase_type', 30)->default('stock');     // stock | direct_job_card | outside_labour | emergency
            $table->string('discount_scheme', 20)->nullable();         // line | bill | cash | scheme
            $table->string('inventory_status', 30)->default('spares_in_transit');
            $table->foreignId('vendor_id')->constrained('vendors')->cascadeOnDelete();
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
            $table->date('challan_date')->nullable();
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

        Schema::create('challan_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('challan_id')->constrained('challans')->cascadeOnDelete();
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
            $table->string('material_condition', 20)->default('new');   // new | used | damaged | repairable | scrap
            $table->string('invoice_status', 20)->default('pending');   // received | pending
            $table->unsignedInteger('sequence_no')->default(0);
            $table->timestamps();

            $table->index(['challan_id', 'sequence_no']);
        });

        Schema::create('challan_charges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('challan_id')->constrained('challans')->cascadeOnDelete();
            $table->foreignId('charge_type_id')->nullable()->constrained('charge_types')->nullOnDelete();
            $table->decimal('amount', 14, 2)->default(0);
            $table->string('notes')->nullable();
            $table->unsignedInteger('sequence_no')->default(0);
            $table->timestamps();
        });

        Schema::create('challan_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('challan_id')->constrained('challans')->cascadeOnDelete();
            $table->string('path');
            $table->string('original_name')->nullable();
            $table->string('mime_type', 100)->nullable();
            $table->unsignedBigInteger('size_bytes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('challan_attachments');
        Schema::dropIfExists('challan_charges');
        Schema::dropIfExists('challan_items');
        Schema::dropIfExists('challans');
    }
};
