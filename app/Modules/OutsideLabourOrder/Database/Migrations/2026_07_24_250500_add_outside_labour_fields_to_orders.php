<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * OLO-specific fields on top of the cloned work-order structure: the vendor /
 * contractor, the originating Outside Labour Inquiry, the order type (reusing
 * the vendor service-speciality catalogue), sequence ordering, and the
 * communication / follow-up channels.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('outside_labour_orders', function (Blueprint $table) {
            $table->foreignId('vendor_id')->nullable()->after('job_card_id')->constrained('vendors')->nullOnDelete();
            $table->foreignId('outside_labour_inquiry_id')->nullable()->after('vendor_id')->constrained('outside_labour_inquiries')->nullOnDelete();
            $table->foreignId('order_type_id')->nullable()->after('outside_labour_inquiry_id')->constrained('service_specialists')->nullOnDelete();
            $table->foreignId('customer_vehicle_id')->nullable()->after('order_type_id')->constrained('customer_vehicles')->nullOnDelete();
            $table->foreignId('follow_up_mode_id')->nullable()->after('order_type_id')->constrained('follow_up_modes')->nullOnDelete();
            $table->string('communication_mode', 20)->nullable()->after('follow_up_mode_id'); // whatsapp / email / phone
            $table->unsignedSmallInteger('sequence_no')->nullable()->after('communication_mode');
        });
    }

    public function down(): void
    {
        Schema::table('outside_labour_orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('vendor_id');
            $table->dropConstrainedForeignId('outside_labour_inquiry_id');
            $table->dropConstrainedForeignId('order_type_id');
            $table->dropConstrainedForeignId('customer_vehicle_id');
            $table->dropConstrainedForeignId('follow_up_mode_id');
            $table->dropColumn(['communication_mode', 'sequence_no']);
        });
    }
};
