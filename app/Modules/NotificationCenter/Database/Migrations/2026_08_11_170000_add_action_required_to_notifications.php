<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Alerts that will not go away until someone deals with them.
 *
 * A read receipt is the wrong model for "car delivery delayed" — reading it
 * changes nothing. These carry `requires_action` and stay on a banner until
 * `resolved_at` is stamped, which only happens when the user does the thing.
 *
 * `role` lets an alert be addressed to a job rather than a person, so
 * "whoever is on the store desk" gets it without naming an employee.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('app_notifications', function (Blueprint $table) {
            $table->string('role', 60)->nullable()->after('employee_id');

            $table->boolean('requires_action')->default(false)->after('url');
            $table->string('severity', 20)->default('info')->after('requires_action'); // info / warning / critical
            $table->string('action_label', 60)->nullable()->after('severity');
            $table->dateTime('resolved_at')->nullable()->after('read_at');
            $table->foreignId('resolved_by_user_id')->nullable()->after('resolved_at')
                ->constrained('users')->nullOnDelete();

            $table->index(['requires_action', 'resolved_at']);
            $table->index(['role', 'resolved_at']);
        });
    }

    public function down(): void
    {
        Schema::table('app_notifications', function (Blueprint $table) {
            $table->dropIndex(['requires_action', 'resolved_at']);
            $table->dropIndex(['role', 'resolved_at']);
            $table->dropConstrainedForeignId('resolved_by_user_id');
            $table->dropColumn(['role', 'requires_action', 'severity', 'action_label', 'resolved_at']);
        });
    }
};
