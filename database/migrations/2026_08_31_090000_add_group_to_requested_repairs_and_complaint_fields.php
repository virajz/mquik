<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Complaints are now picked from the Requested Repair master (standard
 * observations belong to checklist templates, not the counter conversation).
 *
 * A repair carries its own group, so picking one fills the category for free;
 * and each complaint gets its own moment and a repeat-job flag, so an advisor
 * and a technician stop arguing about which of several complaints came when.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('requested_repairs', function (Blueprint $table) {
            $table->foreignId('complaint_type_id')->nullable()->after('code')->constrained('complaint_types')->nullOnDelete();
        });

        Schema::table('job_card_complaints', function (Blueprint $table) {
            $table->foreignId('requested_repair_id')->nullable()->after('standard_observation_id')->constrained('requested_repairs')->nullOnDelete();
            $table->timestamp('reported_at')->nullable()->after('description');
            $table->boolean('is_repeat_job')->default(false)->after('reported_at');
        });
    }

    public function down(): void
    {
        Schema::table('job_card_complaints', function (Blueprint $table) {
            $table->dropConstrainedForeignId('requested_repair_id');
            $table->dropColumn(['reported_at', 'is_repeat_job']);
        });

        Schema::table('requested_repairs', function (Blueprint $table) {
            $table->dropConstrainedForeignId('complaint_type_id');
        });
    }
};
