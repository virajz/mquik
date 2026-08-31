<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Two things the sheet was missing.
 *
 * An advisor on the setup, so the inspection can be owned by the floor or by
 * the front desk — one of the two has to answer for it.
 *
 * And a second notes field. `summary_notes` becomes the internal one; customer
 * notes are what gets printed on the physical checklist the customer sees, and
 * mixing the two in one box means either the workshop censors itself or the
 * customer reads something meant for the bay.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('digital_inspections', function (Blueprint $table) {
            if (! Schema::hasColumn('digital_inspections', 'advisor_id')) {
                $table->foreignId('advisor_id')->nullable()->after('floor_incharge_id')
                    ->constrained('employees')->nullOnDelete();
            }

            if (! Schema::hasColumn('digital_inspections', 'customer_notes')) {
                $table->text('customer_notes')->nullable()->after('summary_notes');
            }
        });
    }

    public function down(): void
    {
        Schema::table('digital_inspections', function (Blueprint $table) {
            $table->dropConstrainedForeignId('advisor_id');
            $table->dropColumn('customer_notes');
        });
    }
};
