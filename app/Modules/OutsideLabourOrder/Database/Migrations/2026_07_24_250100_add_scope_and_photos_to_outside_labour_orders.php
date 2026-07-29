<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Row 9 remainder — the two things the order could not express:
 *
 *  - WHAT is being inspected (complaint / job description / service package).
 *    Service, Combo and AMC packages are all rows in `service_packages`
 *    distinguished by their type, so one FK covers all three.
 *  - Order-level photo evidence (front/rear/side/damage/fault), as distinct
 *    from the before/after pair already stored per checklist item.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('outside_labour_order_scopes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('outside_labour_order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('complaint_type_id')->nullable()->constrained('complaint_types')->nullOnDelete();
            $table->foreignId('job_description_id')->nullable()->constrained('job_descriptions')->nullOnDelete();
            // Covers Service / Combo / AMC — they differ only by package type.
            $table->foreignId('service_package_id')->nullable()->constrained('service_packages')->nullOnDelete();
            $table->text('description');
            $table->unsignedSmallInteger('sequence_no')->default(1);
            $table->timestamps();

            $table->index(['outside_labour_order_id', 'sequence_no'], 'olo_scopes_order_sequence_index');
        });

        Schema::create('outside_labour_order_photos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('outside_labour_order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('photo_type_id')->nullable()->constrained('photo_types')->nullOnDelete();
            $table->string('path');
            $table->string('original_name')->nullable();
            $table->unsignedInteger('size_bytes')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedSmallInteger('sequence_no')->default(1);
            $table->timestamps();

            $table->index(['outside_labour_order_id', 'sequence_no'], 'olo_photos_order_sequence_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('outside_labour_order_photos');
        Schema::dropIfExists('outside_labour_order_scopes');
    }
};
