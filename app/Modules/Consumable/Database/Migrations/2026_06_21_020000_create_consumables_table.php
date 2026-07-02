<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('consumables', function (Blueprint $table) {
            $table->id();
            $table->string('consumable_no', 32)->nullable()->unique();
            $table->foreignId('consumable_category_id')->nullable()->constrained('consumable_categories')->nullOnDelete();
            $table->foreignId('job_card_id')->nullable()->constrained('job_cards')->nullOnDelete();
            $table->foreignId('challan_id')->nullable()->constrained('challans')->nullOnDelete();
            $table->foreignId('purchase_entry_id')->nullable()->constrained('purchase_entries')->nullOnDelete();
            $table->foreignId('loss_type_id')->nullable()->constrained('loss_types')->nullOnDelete();
            $table->foreignId('loss_reason_id')->nullable()->constrained('loss_reasons')->nullOnDelete();
            $table->foreignId('department_id')->nullable()->constrained('workshop_departments')->nullOnDelete();
            $table->foreignId('advisor_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->foreignId('technician_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->foreignId('store_incharge_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->foreignId('vendor_id')->nullable()->constrained('vendors')->nullOnDelete();
            $table->string('approval_authority', 30)->nullable();
            $table->string('approval_status', 20)->default('requested');  // requested | approved | rejected | on_hold
            $table->string('communication_mode', 20)->nullable();
            $table->decimal('parts_value', 14, 2)->default(0);
            $table->decimal('labour_value', 14, 2)->default(0);
            $table->decimal('tax_total', 14, 2)->default(0);
            $table->decimal('total_value', 14, 2)->default(0);
            $table->text('notes')->nullable();
            $table->dateTime('consumed_at')->nullable();
            $table->dateTime('approved_at')->nullable();
            $table->timestamps();

            $table->index(['approval_status', 'created_at']);
            $table->index(['consumable_category_id', 'created_at']);
            $table->index(['job_card_id']);
        });

        Schema::create('consumable_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('consumable_id')->constrained('consumables')->cascadeOnDelete();
            $table->string('line_type', 10);  // spare | labour
            $table->foreignId('spare_id')->nullable()->constrained('spares')->nullOnDelete();
            $table->foreignId('labour_id')->nullable()->constrained('labours')->nullOnDelete();
            $table->foreignId('uom_id')->nullable()->constrained('units_of_measure')->nullOnDelete();
            $table->foreignId('tax_id')->nullable()->constrained('taxes')->nullOnDelete();
            $table->string('description');
            $table->string('hsn_code', 16)->nullable();
            $table->decimal('qty', 10, 2)->default(1);
            $table->decimal('unit_rate', 14, 2)->default(0);
            $table->decimal('tax_percent', 5, 2)->default(0);
            $table->decimal('line_total', 14, 2)->default(0);
            $table->unsignedInteger('sequence_no')->default(0);
            $table->timestamps();

            $table->index(['consumable_id', 'sequence_no']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('consumable_items');
        Schema::dropIfExists('consumables');
    }
};
