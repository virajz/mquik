<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Store-Manager response step on the Internal Parts Inquiry.
 *
 * Records who responded and when the inquiry moved into a responded state
 * (partially / fully / not available / alternative suggested), so the response
 * is auditable rather than just a re-edit of the create form.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('internal_parts_inquiries', function (Blueprint $table) {
            $table->foreignId('responded_by_employee_id')->nullable()->after('target_employee_id')->constrained('employees')->nullOnDelete();
            $table->dateTime('responded_at')->nullable()->after('responded_by_employee_id');
        });
    }

    public function down(): void
    {
        Schema::table('internal_parts_inquiries', function (Blueprint $table) {
            $table->dropConstrainedForeignId('responded_by_employee_id');
            $table->dropColumn('responded_at');
        });
    }
};
