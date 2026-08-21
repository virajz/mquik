<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A phone number on the login.
 *
 * Verification happens by OTP to this number, so it is the account's real
 * identifier for anything security-related. Nullable because existing logins
 * predate it; unique so two accounts cannot claim the same number.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('phone', 20)->nullable()->unique()->after('email');
            $table->dateTime('phone_verified_at')->nullable()->after('phone');
            // Set when an admin creates or resets an account: the user must
            // choose their own password before doing anything else.
            $table->boolean('must_reset_password')->default(false)->after('password');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['phone']);
            $table->dropColumn(['phone', 'phone_verified_at', 'must_reset_password']);
        });
    }
};
