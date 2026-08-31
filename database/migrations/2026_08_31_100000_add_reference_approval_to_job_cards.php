<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The T&Cs can be accepted by the customer or, when they are not there, by a
 * reference standing in for them — a relative, a driver, a fleet manager. Who
 * that was is the whole point of the record, so their name and number are kept.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('job_cards', function (Blueprint $table) {
            $table->string('terms_accepted_by')->nullable()->after('terms_accepted_at');
            $table->string('reference_name')->nullable()->after('terms_accepted_by');
            $table->string('reference_phone', 20)->nullable()->after('reference_name');
        });
    }

    public function down(): void
    {
        Schema::table('job_cards', function (Blueprint $table) {
            $table->dropColumn(['terms_accepted_by', 'reference_name', 'reference_phone']);
        });
    }
};
