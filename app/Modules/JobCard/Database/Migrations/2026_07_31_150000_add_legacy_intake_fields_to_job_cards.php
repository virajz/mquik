<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Intake fields the old ERP captured on a job card that had no home in the new
 * schema — so the legacy import can map (and the UI can show) everything that
 * isn't purely financial: out-odometer, how the vehicle arrived, average
 * mileage, the additional/other-repairs free text, and the old bill number
 * (a reference that will resolve to the imported invoice later).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('job_cards', function (Blueprint $table) {
            $table->unsignedInteger('odometer_out')->nullable()->after('km_at_service');
            $table->string('brought_by', 20)->nullable()->after('odometer_out');   // owner / driver / pickup / towing
            $table->unsignedSmallInteger('avg_mileage')->nullable()->after('brought_by');
            $table->text('additional_work')->nullable()->after('suggested_services');
            $table->string('legacy_bill_no', 40)->nullable()->after('legacy_id');
            $table->index('legacy_bill_no');
        });
    }

    public function down(): void
    {
        Schema::table('job_cards', function (Blueprint $table) {
            $table->dropIndex(['legacy_bill_no']);
            $table->dropColumn(['odometer_out', 'brought_by', 'avg_mileage', 'additional_work', 'legacy_bill_no']);
        });
    }
};
