<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('goods_returns', function (Blueprint $table) {
            $table->id();
            $table->string('return_no', 32)->nullable()->unique();
            $table->string('document_type', 20)->default('credit_note');   // credit_note | debit_note
            $table->string('credit_note_type', 20)->default('tax_credit'); // e_credit | tax_credit | bill_of_supply
            $table->foreignId('credit_note_reason_id')->nullable()->constrained('credit_note_reasons')->nullOnDelete();
            $table->foreignId('vendor_id')->constrained('vendors')->cascadeOnDelete();
            $table->foreignId('transport_mode_id')->nullable()->constrained('transport_modes')->nullOnDelete();
            $table->foreignId('transport_company_id')->nullable()->constrained('courier_companies')->nullOnDelete();
            $table->foreignId('purchase_entry_id')->nullable()->constrained('purchase_entries')->nullOnDelete();
            $table->foreignId('challan_id')->nullable()->constrained('challans')->nullOnDelete();
            $table->string('grn_reference', 60)->nullable();
            $table->string('warranty_type', 30)->nullable();   // no_warranty | vendor | manufacturer
            $table->string('warranty_period', 20)->nullable(); // 3m | 6m | 12m | 24m
            $table->date('returned_at')->nullable();
            $table->decimal('parts_total', 14, 2)->default(0);
            $table->decimal('tax_total', 14, 2)->default(0);
            $table->decimal('grand_total', 14, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['vendor_id', 'created_at']);
            $table->index(['document_type', 'created_at']);
        });

        Schema::create('goods_return_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('goods_return_id')->constrained('goods_returns')->cascadeOnDelete();
            $table->foreignId('spare_id')->nullable()->constrained('spares')->nullOnDelete();
            $table->foreignId('uom_id')->nullable()->constrained('units_of_measure')->nullOnDelete();
            $table->foreignId('tax_id')->nullable()->constrained('taxes')->nullOnDelete();
            $table->string('description');
            $table->string('hsn_code', 16)->nullable();
            $table->decimal('qty', 10, 2)->default(1);
            $table->decimal('unit_rate', 14, 2)->default(0);
            $table->decimal('tax_percent', 5, 2)->default(0);
            $table->decimal('line_total', 14, 2)->default(0);
            $table->string('material_condition', 20)->default('new');  // new | used | unused | open_box
            $table->unsignedInteger('sequence_no')->default(0);
            $table->timestamps();

            $table->index(['goods_return_id', 'sequence_no']);
        });

        Schema::create('goods_return_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('goods_return_id')->constrained('goods_returns')->cascadeOnDelete();
            $table->string('path');
            $table->string('original_name')->nullable();
            $table->string('mime_type', 100)->nullable();
            $table->unsignedBigInteger('size_bytes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('goods_return_attachments');
        Schema::dropIfExists('goods_return_items');
        Schema::dropIfExists('goods_returns');
    }
};
