<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * One RFQ, many vendors.
 *
 * The workshop sends the same parts inquiry to several vendors and compares what
 * comes back, but the inquiry only carried a single `vendor_id`. Recipients move
 * to their own table so each vendor's dispatch and reply is tracked separately.
 *
 * `vendor_purchase_inquiries.vendor_id` is deliberately left in place: it still
 * names the vendor finally chosen, and dropping it would break the VPI → VPO
 * carry-forward. Existing single-vendor inquiries are backfilled as one recipient.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vendor_purchase_inquiry_vendors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_purchase_inquiry_id')->constrained()->cascadeOnDelete();
            $table->foreignId('vendor_id')->constrained('vendors')->cascadeOnDelete();

            // Dispatch tracking, per vendor.
            $table->string('dispatch_channel', 20)->nullable();   // whatsapp / email / phone / portal
            $table->dateTime('sent_at')->nullable();
            $table->dateTime('responded_at')->nullable();
            $table->string('response_status', 20)->default('awaiting'); // awaiting / quoted / declined / no_response
            $table->decimal('quoted_total', 14, 2)->nullable();
            $table->unsignedSmallInteger('lead_time_days')->nullable();
            $table->string('warranty_summary', 120)->nullable();
            $table->boolean('is_selected')->default(false);       // the vendor the PO went to
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['vendor_purchase_inquiry_id', 'vendor_id'], 'vpi_vendor_unique');
            $table->index(['vendor_purchase_inquiry_id', 'response_status'], 'vpi_vendor_status_index');
        });

        // Every existing inquiry already went to exactly one vendor.
        DB::table('vendor_purchase_inquiries')
            ->whereNotNull('vendor_id')
            ->orderBy('id')
            ->chunkById(500, function ($rows) {
                $now = now();
                $insert = [];
                foreach ($rows as $row) {
                    $insert[] = [
                        'vendor_purchase_inquiry_id' => $row->id,
                        'vendor_id' => $row->vendor_id,
                        'response_status' => 'awaiting',
                        'is_selected' => true,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }
                if ($insert !== []) {
                    DB::table('vendor_purchase_inquiry_vendors')->insertOrIgnore($insert);
                }
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('vendor_purchase_inquiry_vendors');
    }
};
