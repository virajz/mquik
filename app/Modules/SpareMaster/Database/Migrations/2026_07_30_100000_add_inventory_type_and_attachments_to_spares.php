<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Module 98 — Spares Master (inventory-type + attachments).
 *
 * Adds the two remaining spec fields: a fixed `inventory_type` classification
 * (Accessories / Body Parts / Consumables / Lubricants / Mechanical / Tyres /
 * Wheel Rim & Parts) and a document-attachments child (Spares Image /
 * Application Guide). Everything else in the spec — application type
 * (spare_type), vehicle compatibility (spare_vehicle_variants), spares type
 * (part_type_id), brand, department, group / sub-group, rack / location, UOM,
 * HSN & tax — already exists on the module.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('spares', function (Blueprint $table) {
            $table->string('inventory_type', 25)->nullable()->after('inventory_sub_group_id'); // accessories / body_parts / …
            $table->index('inventory_type');
        });

        Schema::create('spare_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('spare_id')->constrained('spares')->cascadeOnDelete();
            $table->string('attachment_type', 20)->nullable(); // spare_image / application_guide
            $table->string('kind', 10)->default('image');
            $table->string('path');
            $table->string('original_name')->nullable();
            $table->unsignedInteger('size_bytes')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedSmallInteger('sequence_no')->default(1);
            $table->timestamps();

            $table->index(['spare_id', 'sequence_no'], 'spare_attachments_spare_sequence_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('spare_attachments');

        Schema::table('spares', function (Blueprint $table) {
            $table->dropIndex(['inventory_type']);
            $table->dropColumn('inventory_type');
        });
    }
};
