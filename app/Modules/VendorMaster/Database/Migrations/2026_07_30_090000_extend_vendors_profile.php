<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Module 97 — Vendor Master (profile extension).
 *
 * Adds the spec's missing profile fields: legal/trade name split, registration
 * date & reference, contact persons, branch address + pincode, Udyam no,
 * business-classification enums (classification / constitution / GST reg-type /
 * MSME type & activity / vendor category), lifecycle status + rating +
 * blacklist reason, delivery method, and the performance KPIs — plus a
 * document-attachments child and a T&Cs-type on the terms child.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vendors', function (Blueprint $table) {
            $table->string('legal_name')->nullable()->after('name');
            $table->date('registration_date')->nullable()->after('legal_name');
            $table->string('reference')->nullable()->after('registration_date');
            $table->string('contact_person1')->nullable()->after('reference');
            $table->string('contact_person2')->nullable()->after('contact_person1');

            $table->text('branch_address')->nullable()->after('address');
            $table->char('pincode', 6)->nullable()->after('branch_address');
            $table->string('udyam_no', 25)->nullable()->after('gstin');

            $table->string('classification', 30)->nullable();       // manufacturer / distributor / dealer / …
            $table->string('constitution', 30)->nullable();         // sole_proprietorship / partnership / llp / private_limited
            $table->string('gst_registration_type', 20)->nullable(); // regular / sez / export / composition / unregistered
            $table->string('msme_type', 15)->nullable();            // micro / small / medium / enterprise
            $table->string('msme_activity', 15)->nullable();        // trading / services / manufacturing
            $table->string('vendor_category', 30)->nullable();      // genuine / oem / aftermarket / scrap_refurbished

            $table->string('vendor_status', 15)->default('active'); // active / inactive / prospect / suspended / blacklisted / closed
            $table->string('blacklist_reason', 25)->nullable();
            $table->unsignedTinyInteger('rating')->nullable();      // 1..5
            $table->string('delivery_method', 20)->nullable();      // self_pickup / vendor_delivery / courier / transport

            $table->decimal('on_time_delivery_percent', 5, 2)->nullable();
            $table->decimal('parts_return_percent', 5, 2)->nullable();
            $table->decimal('return_rejection_percent', 5, 2)->nullable();
            $table->decimal('avg_response_hours', 6, 2)->nullable();

            $table->index('vendor_status');
            $table->index('rating');
        });

        Schema::table('vendor_terms', function (Blueprint $table) {
            $table->string('term_type', 20)->nullable()->after('vendor_id'); // payment_policy / delivery_policy / return_policy / warranty_policy
        });

        Schema::create('vendor_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->constrained('vendors')->cascadeOnDelete();
            $table->string('attachment_type', 25)->nullable(); // gst_certificate / pan_card / msme_certificate / cancelled_cheque / bank_passbook / tcs_signed_copy
            $table->string('kind', 10)->default('image');
            $table->string('path');
            $table->string('original_name')->nullable();
            $table->unsignedInteger('size_bytes')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedSmallInteger('sequence_no')->default(1);
            $table->timestamps();

            $table->index(['vendor_id', 'sequence_no'], 'vendor_attachments_vendor_sequence_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vendor_attachments');

        Schema::table('vendor_terms', function (Blueprint $table) {
            $table->dropColumn('term_type');
        });

        Schema::table('vendors', function (Blueprint $table) {
            $table->dropIndex(['vendor_status']);
            $table->dropIndex(['rating']);
            $table->dropColumn([
                'legal_name', 'registration_date', 'reference', 'contact_person1', 'contact_person2',
                'branch_address', 'pincode', 'udyam_no', 'classification', 'constitution',
                'gst_registration_type', 'msme_type', 'msme_activity', 'vendor_category',
                'vendor_status', 'blacklist_reason', 'rating', 'delivery_method',
                'on_time_delivery_percent', 'parts_return_percent', 'return_rejection_percent', 'avg_response_hours',
            ]);
        });
    }
};
