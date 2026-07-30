<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Drive job-card complaints from the Standard Observation master instead of
 * free-typed text. Each complaint line now references a common-complaint phrase
 * (`standard_observation_id`); the `description` column is kept and populated
 * from that phrase so existing readers/reports keep working.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('job_card_complaints', function (Blueprint $table) {
            $table->foreignId('standard_observation_id')->nullable()->after('complaint_type_id')->constrained('standard_observations')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('job_card_complaints', function (Blueprint $table) {
            $table->dropConstrainedForeignId('standard_observation_id');
        });
    }
};
