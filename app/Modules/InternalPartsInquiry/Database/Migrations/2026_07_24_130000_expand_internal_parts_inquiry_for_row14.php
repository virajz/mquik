<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Requirement row 14 (IPI) — grow the thin mid-build module into the full
 * spec: department/customer/vehicle/vendor/priority context, inquiry type,
 * TAT, approval authority, rejection reason, and a 9-state status on the
 * parent; commercial + stock + alternative columns on each line; and a
 * photo/attachment child (Before/After + Damage/Fault, PDF or image).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('internal_parts_inquiries', function (Blueprint $table) {
            $table->foreignId('workshop_department_id')->nullable()->after('job_card_id')->constrained('workshop_departments')->nullOnDelete();
            $table->foreignId('customer_id')->nullable()->after('workshop_department_id')->constrained('customers')->nullOnDelete();
            $table->foreignId('customer_vehicle_id')->nullable()->after('customer_id')->constrained('customer_vehicles')->nullOnDelete();
            $table->foreignId('vendor_id')->nullable()->after('customer_vehicle_id')->constrained('vendors')->nullOnDelete();
            $table->foreignId('priority_id')->nullable()->after('vendor_id')->constrained('priorities')->nullOnDelete();
            $table->foreignId('rejection_reason_id')->nullable()->after('priority_id')->constrained('ipi_rejection_reasons')->nullOnDelete();

            $table->string('inquiry_type', 30)->nullable()->after('rejection_reason_id'); // against_job_card / stock_replenishment / special_order / emergency_requirement
            $table->string('approval_authority', 30)->nullable()->after('inquiry_type'); // service_advisor / store_manager / workshop_manager / owner / admin
            $table->string('tat_option', 20)->nullable()->after('approval_authority'); // immediate / same_day / next_day / two_three_days / custom
            $table->unsignedSmallInteger('tat_custom_days')->nullable()->after('tat_option');
        });

        // Stock Replenishment / Special Order / Emergency inquiries have no job card.
        Schema::table('internal_parts_inquiries', function (Blueprint $table) {
            $table->foreignId('job_card_id')->nullable()->change();
        });

        // Widen + re-vocabulary the status column (longest new value is 21 chars).
        Schema::table('internal_parts_inquiries', function (Blueprint $table) {
            $table->string('status', 30)->default('pending')->change();
        });

        // Remap the old 4-state vocabulary onto the new 9-state set.
        DB::table('internal_parts_inquiries')->where('status', 'open')->update(['status' => 'pending']);
        DB::table('internal_parts_inquiries')->where('status', 'responded')->update(['status' => 'in_progress']);
        DB::table('internal_parts_inquiries')->where('status', 'closed')->update(['status' => 'completed']);

        Schema::table('internal_parts_inquiry_items', function (Blueprint $table) {
            $table->foreignId('spare_brand_id')->nullable()->after('spare_id')->constrained('spare_brands')->nullOnDelete();
            $table->foreignId('part_type_id')->nullable()->after('spare_brand_id')->constrained('part_types')->nullOnDelete(); // Genuine / Aftermarket / OEM / Refurbished
            $table->foreignId('uom_id')->nullable()->after('part_type_id')->constrained('units_of_measure')->nullOnDelete();
            $table->foreignId('hsn_id')->nullable()->after('uom_id')->constrained('hsn_codes')->nullOnDelete();
            $table->foreignId('tax_id')->nullable()->after('hsn_id')->constrained('taxes')->nullOnDelete();
            $table->foreignId('vehicle_variant_id')->nullable()->after('tax_id')->constrained('vehicle_variants')->nullOnDelete();
            $table->decimal('rate_before_tax', 12, 2)->nullable()->after('quantity'); // store's quoted rate
            $table->string('stock_status', 20)->nullable()->after('rate_before_tax'); // available / not_available / reserved / issued / ordered / in_transit / backorder
            $table->string('alternative_option', 20)->nullable()->after('stock_status'); // primary / alternate_part / alternate_brand
        });

        Schema::create('internal_parts_inquiry_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('internal_parts_inquiry_id')->constrained('internal_parts_inquiries')->cascadeOnDelete();
            $table->foreignId('photo_type_id')->nullable()->constrained('photo_types')->nullOnDelete();
            $table->string('kind', 10)->default('image'); // image / pdf
            $table->string('path');
            $table->string('original_name')->nullable();
            $table->unsignedInteger('size_bytes')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedSmallInteger('sequence_no')->default(1);
            $table->timestamps();

            $table->index(['internal_parts_inquiry_id', 'sequence_no'], 'ipi_attachments_inquiry_sequence_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('internal_parts_inquiry_attachments');

        Schema::table('internal_parts_inquiry_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('spare_brand_id');
            $table->dropConstrainedForeignId('part_type_id');
            $table->dropConstrainedForeignId('uom_id');
            $table->dropConstrainedForeignId('hsn_id');
            $table->dropConstrainedForeignId('tax_id');
            $table->dropConstrainedForeignId('vehicle_variant_id');
            $table->dropColumn(['rate_before_tax', 'stock_status', 'alternative_option']);
        });

        Schema::table('internal_parts_inquiries', function (Blueprint $table) {
            $table->dropConstrainedForeignId('workshop_department_id');
            $table->dropConstrainedForeignId('customer_id');
            $table->dropConstrainedForeignId('customer_vehicle_id');
            $table->dropConstrainedForeignId('vendor_id');
            $table->dropConstrainedForeignId('priority_id');
            $table->dropConstrainedForeignId('rejection_reason_id');
            $table->dropColumn(['inquiry_type', 'approval_authority', 'tat_option', 'tat_custom_days']);
        });
    }
};
