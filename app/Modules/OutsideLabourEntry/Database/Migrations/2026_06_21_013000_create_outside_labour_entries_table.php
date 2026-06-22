<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('outside_labour_entries', function (Blueprint $table) {
            $table->id();
            $table->string('entry_no', 32)->nullable()->unique();
            $table->foreignId('vendor_id')->constrained('vendors')->cascadeOnDelete();
            $table->foreignId('vendor_type_id')->nullable()->constrained('vendor_types')->nullOnDelete();
            $table->foreignId('job_card_id')->nullable()->constrained('job_cards')->nullOnDelete();
            $table->foreignId('department_id')->nullable()->constrained('workshop_departments')->nullOnDelete();
            $table->foreignId('advisor_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->foreignId('technician_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->foreignId('loss_reason_id')->nullable()->constrained('loss_reasons')->nullOnDelete();
            $table->foreignId('transport_mode_id')->nullable()->constrained('transport_modes')->nullOnDelete();
            $table->string('outside_work_order_ref', 60)->nullable();
            $table->string('invoice_no', 60)->nullable();
            $table->date('invoice_date')->nullable();
            $table->decimal('parts_total', 14, 2)->default(0);
            $table->decimal('labour_total', 14, 2)->default(0);
            $table->decimal('tax_total', 14, 2)->default(0);
            $table->decimal('grand_total', 14, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['vendor_id', 'created_at']);
            $table->index(['job_card_id']);
        });

        Schema::create('outside_labour_entry_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('outside_labour_entry_id')->constrained('outside_labour_entries')->cascadeOnDelete();
            $table->string('line_type', 10);  // spare | labour
            $table->foreignId('spare_id')->nullable()->constrained('spares')->nullOnDelete();
            $table->foreignId('labour_id')->nullable()->constrained('labours')->nullOnDelete();
            $table->foreignId('tax_id')->nullable()->constrained('taxes')->nullOnDelete();
            $table->string('description');
            $table->string('hsn_code', 16)->nullable();
            $table->decimal('qty', 10, 2)->default(1);
            $table->decimal('unit_rate', 14, 2)->default(0);
            $table->decimal('tax_percent', 5, 2)->default(0);
            $table->decimal('line_total', 14, 2)->default(0);
            $table->unsignedInteger('sequence_no')->default(0);
            $table->timestamps();

            $table->index(['outside_labour_entry_id', 'sequence_no']);
        });

        Schema::create('outside_labour_entry_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('outside_labour_entry_id')->constrained('outside_labour_entries')->cascadeOnDelete();
            $table->string('attachment_type', 30)->default('invoice');  // invoice | quote | work_order | report | before_photo | after_photo
            $table->string('path');
            $table->string('original_name')->nullable();
            $table->string('mime_type', 100)->nullable();
            $table->unsignedBigInteger('size_bytes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('outside_labour_entry_attachments');
        Schema::dropIfExists('outside_labour_entry_items');
        Schema::dropIfExists('outside_labour_entries');
    }
};
