<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Say who a login actually is.
 *
 * A user was just a name and an email, unconnected to the employee doing the
 * work — which is why the technician bench and the notification bell have to ask
 * "who are you?" on every shared screen. A login now points at the employee or
 * the service contractor it belongs to.
 *
 * Both are unique: one person, one login. `user_type` records which of the two
 * (or neither, for a plain manual account) so the form can be re-opened in the
 * same mode it was created in.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('user_type', 20)->default('manual')->after('phone'); // employee / contractor / manual
            $table->foreignId('employee_id')->nullable()->unique()->after('user_type')
                ->constrained('employees')->nullOnDelete();
            $table->foreignId('vendor_id')->nullable()->unique()->after('employee_id')
                ->constrained('vendors')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('employee_id');
            $table->dropConstrainedForeignId('vendor_id');
            $table->dropColumn('user_type');
        });
    }
};
