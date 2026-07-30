<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Carry-forward link: a Vendor Purchase Inquiry can originate from an Internal
 * Parts Inquiry (the Store Manager forwarding the parts it can't supply
 * in-house to a vendor RFQ).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vendor_purchase_inquiries', function (Blueprint $table) {
            $table->foreignId('internal_parts_inquiry_id')->nullable()->after('job_card_id')->constrained('internal_parts_inquiries')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('vendor_purchase_inquiries', function (Blueprint $table) {
            $table->dropConstrainedForeignId('internal_parts_inquiry_id');
        });
    }
};
