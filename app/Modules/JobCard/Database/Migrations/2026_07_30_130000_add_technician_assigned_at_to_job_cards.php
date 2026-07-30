<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Record WHEN a technician was assigned to the job card.
 *
 * The card already carried `assigned_technician_id`, but not the moment it was
 * set — the workshop needs to see the assignment time. Stamped on the model's
 * `saving` hook whenever the assigned technician changes.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('job_cards', function (Blueprint $table) {
            $table->dateTime('technician_assigned_at')->nullable()->after('assigned_technician_id');
        });
    }

    public function down(): void
    {
        Schema::table('job_cards', function (Blueprint $table) {
            $table->dropColumn('technician_assigned_at');
        });
    }
};
