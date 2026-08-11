<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The customer's yes, recorded against the RFQ.
 *
 * Between "the vendor quoted" and "we placed the order" the customer has to
 * approve the spend. That approval already exists as a record (customer_id,
 * job_card_id, customer_approved_at); this links the RFQ to it so the purchase
 * order can state what it was authorised by.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vendor_purchase_inquiries', function (Blueprint $table) {
            $table->foreignId('customer_approval_id')->nullable()->after('internal_parts_inquiry_id')
                ->constrained('sales_estimate_approvals')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('vendor_purchase_inquiries', function (Blueprint $table) {
            $table->dropConstrainedForeignId('customer_approval_id');
        });
    }
};
