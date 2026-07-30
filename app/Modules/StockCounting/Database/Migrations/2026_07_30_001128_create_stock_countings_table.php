<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Module 83 — Stock Counting.
 *
 * Physical & system stock verification: a counting session (QR/barcode, RFID or
 * manual) over a storage location / inventory group, counted by a team, that
 * records each spare's system stock vs physical stock and the resulting variance
 * (with a reason). Header + counted-item lines + document attachments (count
 * sheet, management approval, damage photos). Doc series: `SC-#####`.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_counts', function (Blueprint $table) {
            $table->id();
            $table->string('count_no')->nullable()->unique();

            $table->date('count_start_date')->nullable();
            $table->date('count_end_date')->nullable();
            $table->string('counting_method', 15)->nullable(); // qr_barcode / rfid / manual
            $table->string('verification_status', 15)->default('pending'); // pending / in_progress / completed / cancelled

            $table->foreignId('storage_location_id')->nullable()->constrained('racks')->nullOnDelete();
            $table->foreignId('inventory_group_id')->nullable()->constrained('inventory_groups')->nullOnDelete();
            $table->foreignId('team_leader_id')->nullable()->constrained('employees')->nullOnDelete();

            $table->string('team_name')->nullable();
            $table->string('team_members')->nullable(); // free-text list
            $table->text('notes')->nullable();
            $table->text('remarks')->nullable();
            $table->timestamps();

            $table->index('verification_status');
            $table->index('count_start_date');
        });

        Schema::create('stock_count_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_count_id')->constrained()->cascadeOnDelete();
            $table->foreignId('spare_id')->nullable()->constrained('spares')->nullOnDelete();
            $table->foreignId('uom_id')->nullable()->constrained('units_of_measure')->nullOnDelete();
            $table->foreignId('hsn_id')->nullable()->constrained('hsn_codes')->nullOnDelete();
            $table->foreignId('tax_id')->nullable()->constrained('taxes')->nullOnDelete();

            $table->string('barcode')->nullable();
            $table->string('description');
            $table->decimal('quantity', 12, 2)->default(0); // physical count qty entered
            $table->decimal('system_stock', 12, 2)->default(0);
            $table->decimal('physical_stock', 12, 2)->default(0);
            $table->decimal('diff_qty', 12, 2)->default(0); // physical - system
            $table->decimal('net_total', 12, 2)->nullable();
            $table->string('mismatch_reason', 30)->nullable(); // variance reason
            $table->string('spares_condition', 30)->nullable();
            $table->date('purchase_date')->nullable();
            $table->string('purchase_invoice_no')->nullable();
            $table->string('vendor_name')->nullable();
            $table->dateTime('entry_at')->nullable();
            $table->string('remark')->nullable();
            $table->unsignedSmallInteger('sequence_no')->default(1);
            $table->timestamps();

            $table->index(['stock_count_id', 'sequence_no'], 'stock_count_items_count_sequence_index');
        });

        Schema::create('stock_count_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_count_id')->constrained()->cascadeOnDelete();
            $table->string('attachment_type', 25)->nullable(); // physical_count_sheet / management_approval / damage_photo
            $table->string('kind', 10)->default('image');
            $table->string('path');
            $table->string('original_name')->nullable();
            $table->unsignedInteger('size_bytes')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedSmallInteger('sequence_no')->default(1);
            $table->timestamps();

            $table->index(['stock_count_id', 'sequence_no'], 'stock_count_attachments_count_sequence_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_count_attachments');
        Schema::dropIfExists('stock_count_items');
        Schema::dropIfExists('stock_counts');
    }
};
