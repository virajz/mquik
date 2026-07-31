<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Link a pickup/drop to the job card it feeds. A vehicle collected via a
 * pickup can now be tied directly to its job card (previously only reachable
 * indirectly through the shared appointment).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pickup_drops', function (Blueprint $table) {
            $table->foreignId('job_card_id')->nullable()->after('appointment_id')->constrained('job_cards')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('pickup_drops', function (Blueprint $table) {
            $table->dropConstrainedForeignId('job_card_id');
        });
    }
};
