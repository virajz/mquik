<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Vendors can be more than one type ("Spare Parts" + "OSL"), and they carry
 * arbitrary key/value terms (Payment Term, Delivery Term, Warranty, etc.).
 *
 * - Adds `vendor_vendor_type` pivot, backfills from `vendors.vendor_type_id`,
 *   then drops the singular FK column.
 * - Adds `vendor_terms` table, backfills from `vendors.payment_terms` as a
 *   single "Payment Term" row, then drops the column.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Pivot for multi-type assignment.
        Schema::create('vendor_vendor_type', function (Blueprint $table) {
            $table->foreignId('vendor_id')->constrained('vendors')->cascadeOnDelete();
            $table->foreignId('vendor_type_id')->constrained('vendor_types')->restrictOnDelete();
            $table->primary(['vendor_id', 'vendor_type_id']);
        });

        // Free-text key/value terms attached to a vendor.
        Schema::create('vendor_terms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->constrained('vendors')->cascadeOnDelete();
            $table->string('name', 100);  // e.g. "Payment Term", "Delivery Term"
            $table->text('value');        // e.g. "Net 30 days", "FOB Mumbai"
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index('vendor_id');
        });

        // Backfill pivot from the existing single-type column.
        $rows = DB::table('vendors')
            ->whereNotNull('vendor_type_id')
            ->select('id', 'vendor_type_id')
            ->get();
        foreach ($rows as $row) {
            DB::table('vendor_vendor_type')->insertOrIgnore([
                'vendor_id' => $row->id,
                'vendor_type_id' => $row->vendor_type_id,
            ]);
        }

        // Backfill terms from the existing free-text payment_terms column.
        $termRows = DB::table('vendors')
            ->whereNotNull('payment_terms')
            ->where('payment_terms', '!=', '')
            ->select('id', 'payment_terms')
            ->get();
        foreach ($termRows as $row) {
            DB::table('vendor_terms')->insert([
                'vendor_id' => $row->id,
                'name' => 'Payment Term',
                'value' => $row->payment_terms,
                'sort_order' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Drop the now-redundant singular FK + the old free-text column.
        Schema::table('vendors', function (Blueprint $table) {
            $table->dropIndex(['vendor_type_id', 'name']);
            $table->dropForeign(['vendor_type_id']);
            $table->dropColumn(['vendor_type_id', 'payment_terms']);
        });
    }

    public function down(): void
    {
        Schema::table('vendors', function (Blueprint $table) {
            $table->foreignId('vendor_type_id')->nullable()->after('name')->constrained('vendor_types')->restrictOnDelete();
            $table->string('payment_terms')->nullable();
        });

        // Restore the FIRST type per vendor as the singular FK.
        $pairs = DB::table('vendor_vendor_type')
            ->select('vendor_id', DB::raw('MIN(vendor_type_id) as vendor_type_id'))
            ->groupBy('vendor_id')
            ->get();
        foreach ($pairs as $p) {
            DB::table('vendors')->where('id', $p->vendor_id)->update(['vendor_type_id' => $p->vendor_type_id]);
        }

        // Restore payment_terms with the first matching term row.
        $termRows = DB::table('vendor_terms')
            ->where('name', 'Payment Term')
            ->orderBy('id')
            ->get()
            ->groupBy('vendor_id');
        foreach ($termRows as $vendorId => $rows) {
            DB::table('vendors')->where('id', $vendorId)->update(['payment_terms' => $rows->first()->value]);
        }

        Schema::table('vendors', function (Blueprint $table) {
            $table->index(['vendor_type_id', 'name']);
        });

        Schema::dropIfExists('vendor_terms');
        Schema::dropIfExists('vendor_vendor_type');
    }
};
