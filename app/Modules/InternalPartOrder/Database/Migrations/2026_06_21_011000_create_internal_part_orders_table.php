<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('internal_part_orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_no', 32)->nullable()->unique();
            $table->string('ipo_type', 30)->default('job_card_requirement');
            $table->string('order_priority', 20)->default('normal');
            $table->string('status', 30)->default('draft');

            $table->foreignId('job_card_id')->nullable()->constrained('job_cards')->nullOnDelete();
            $table->foreignId('sales_estimate_id')->nullable()->constrained('sales_estimates')->nullOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->foreignId('customer_vehicle_id')->nullable()->constrained('customer_vehicles')->nullOnDelete();
            $table->foreignId('department_id')->nullable()->constrained('workshop_departments')->nullOnDelete();
            $table->foreignId('service_type_id')->nullable()->constrained('service_types')->nullOnDelete();

            $table->foreignId('requested_by_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->foreignId('technician_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->foreignId('store_incharge_id')->nullable()->constrained('employees')->nullOnDelete();

            $table->string('approval_authority', 30)->nullable();
            $table->string('approval_status', 20)->default('pending');
            $table->foreignId('approved_by_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->foreignId('rejection_reason_id')->nullable()->constrained('ipo_rejection_reasons')->nullOnDelete();
            $table->foreignId('cancellation_reason_id')->nullable()->constrained('ipo_cancellation_reasons')->nullOnDelete();

            $table->text('notes')->nullable();
            $table->dateTime('requested_at')->nullable();
            $table->dateTime('approved_at')->nullable();
            $table->dateTime('issued_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index(['job_card_id', 'status']);
            $table->index(['approval_status', 'status']);
        });

        Schema::create('internal_part_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('internal_part_order_id')->constrained('internal_part_orders')->cascadeOnDelete();
            $table->foreignId('spare_id')->nullable()->constrained('spares')->nullOnDelete();
            $table->foreignId('uom_id')->nullable()->constrained('units_of_measure')->nullOnDelete();
            $table->foreignId('return_type_id')->nullable()->constrained('return_types')->nullOnDelete();
            $table->string('description');
            $table->boolean('is_alternate')->default(false);  // primary vs alternate part
            $table->decimal('qty_requested', 10, 2)->default(1);
            $table->decimal('qty_issued', 10, 2)->default(0);
            $table->decimal('qty_returned', 10, 2)->default(0);
            $table->string('stock_status', 20)->default('available');  // available | reserved | issued | out_of_stock | in_transit
            $table->string('issue_status', 20)->default('pending');    // pending | issued | partially_issued | returned | backorder
            $table->string('return_status', 20)->nullable();           // pending | accepted | rejected
            $table->string('before_photo_path')->nullable();
            $table->string('after_photo_path')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedInteger('sequence_no')->default(0);
            $table->timestamps();

            $table->index(['internal_part_order_id', 'sequence_no']);
            $table->index(['internal_part_order_id', 'issue_status']);
        });

        Schema::create('internal_part_order_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('internal_part_order_id')->constrained('internal_part_orders')->cascadeOnDelete();
            $table->string('path');
            $table->string('original_name')->nullable();
            $table->string('mime_type', 100)->nullable();
            $table->unsignedBigInteger('size_bytes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('internal_part_order_attachments');
        Schema::dropIfExists('internal_part_order_items');
        Schema::dropIfExists('internal_part_orders');
    }
};
