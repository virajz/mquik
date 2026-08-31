<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Complaint severity goes.
 *
 * It left the job-card form when complaints became a pick from the Requested
 * Repairs master rather than free text, so nothing has set it since — every
 * remaining row carries the column default. Priority already says how urgent
 * the work is, at the level the workshop actually schedules on.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('job_card_complaints', 'severity')) {
            Schema::table('job_card_complaints', function (Blueprint $table) {
                $table->dropColumn('severity');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasColumn('job_card_complaints', 'severity')) {
            Schema::table('job_card_complaints', function (Blueprint $table) {
                $table->string('severity', 10)->default('medium')->after('description');
            });
        }
    }
};
