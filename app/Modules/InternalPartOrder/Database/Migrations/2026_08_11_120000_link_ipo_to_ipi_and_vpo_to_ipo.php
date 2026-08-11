<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Close two gaps in the parts chain.
 *
 *  - An Internal Part Order should remember the Internal Parts Inquiry it came
 *    from (IPI → IPO), the in-house equivalent of the IPI → VPI carry-forward.
 *  - A Vendor Purchase Order should remember the Internal Part Order it escalated
 *    from (IPO → VPO), for the case where the store cannot supply after all.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('internal_part_orders', function (Blueprint $table) {
            $table->foreignId('internal_parts_inquiry_id')->nullable()->after('job_card_id')
                ->constrained('internal_parts_inquiries')->nullOnDelete();
        });

        Schema::table('vendor_purchase_orders', function (Blueprint $table) {
            $table->foreignId('internal_part_order_id')->nullable()->after('vendor_purchase_inquiry_id')
                ->constrained('internal_part_orders')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('internal_part_orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('internal_parts_inquiry_id');
        });

        Schema::table('vendor_purchase_orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('internal_part_order_id');
        });
    }
};
