<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Several rates against one requested part.
 *
 * A line previously held a single `quoted_rate`, which cannot answer the two
 * questions an advisor actually asks: what do different vendors charge for this,
 * and what is the genuine-versus-aftermarket trade-off? Both are the same shape —
 * one requested part, many priced offers — so quotes get their own table keyed by
 * (line, vendor, part grade, brand).
 *
 * The line's own `quoted_rate` is left in place and kept in step with whichever
 * quote is selected, so existing screens and the VPI → VPO carry-forward keep
 * working unchanged.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vendor_purchase_inquiry_quotes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_purchase_inquiry_item_id')->constrained()->cascadeOnDelete();
            $table->foreignId('vendor_id')->nullable()->constrained('vendors')->nullOnDelete();

            // The trade-off axis: genuine / OEM / aftermarket / refurbished, and brand.
            $table->foreignId('part_type_id')->nullable()->constrained('part_types')->nullOnDelete();
            $table->foreignId('spare_brand_id')->nullable()->constrained('spare_brands')->nullOnDelete();

            $table->decimal('rate', 12, 2)->nullable();
            $table->decimal('discount_value', 12, 2)->nullable();
            $table->string('warranty_type', 20)->nullable();
            $table->unsignedSmallInteger('warranty_period_value')->nullable();
            $table->string('warranty_period_unit', 10)->nullable();
            $table->unsignedSmallInteger('lead_time_days')->nullable();
            $table->string('availability', 20)->nullable();  // in_stock / to_order / not_available

            $table->boolean('is_selected')->default(false);  // the quote the PO uses
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['vendor_purchase_inquiry_item_id', 'is_selected'], 'vpi_quotes_line_selected_index');
            $table->index('vendor_id');
        });

        // Existing lines already carry one rate — keep it as the selected quote so
        // nothing appears to lose its price.
        DB::table('vendor_purchase_inquiry_items')
            ->whereNotNull('quoted_rate')
            ->orderBy('id')
            ->chunkById(500, function ($rows) {
                $now = now();
                $insert = [];
                foreach ($rows as $row) {
                    $vendorId = DB::table('vendor_purchase_inquiries')
                        ->where('id', $row->vendor_purchase_inquiry_id)
                        ->value('vendor_id');

                    $insert[] = [
                        'vendor_purchase_inquiry_item_id' => $row->id,
                        'vendor_id' => $vendorId,
                        'part_type_id' => $row->part_type_id,
                        'spare_brand_id' => $row->spare_brand_id,
                        'rate' => $row->quoted_rate,
                        'discount_value' => $row->discount_value,
                        'warranty_type' => $row->warranty_type,
                        'warranty_period_value' => $row->warranty_period_value,
                        'warranty_period_unit' => $row->warranty_period_unit,
                        'lead_time_days' => $row->lead_time_days,
                        'is_selected' => true,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }
                if ($insert !== []) {
                    DB::table('vendor_purchase_inquiry_quotes')->insert($insert);
                }
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('vendor_purchase_inquiry_quotes');
    }
};
