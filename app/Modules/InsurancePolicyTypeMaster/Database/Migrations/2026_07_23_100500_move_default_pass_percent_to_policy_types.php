<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The pass percentage is a property of the policy (Comprehensive, Third Party,
 * Zero Dep, Corporate) rather than of the insurer, so it moves to the policy
 * type master.
 *
 * The old column on insurance_companies had no readers anywhere in the app, so
 * there is nothing to carry across — the new column simply starts at its
 * default and is set per policy type.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('insurance_policy_types', function (Blueprint $table) {
            $table->decimal('default_pass_percent', 5, 2)->default(100)->after('code');
        });

        Schema::table('insurance_companies', function (Blueprint $table) {
            $table->dropColumn('default_pass_percent');
        });
    }

    public function down(): void
    {
        Schema::table('insurance_companies', function (Blueprint $table) {
            $table->decimal('default_pass_percent', 5, 2)->default(100);
        });

        Schema::table('insurance_policy_types', function (Blueprint $table) {
            $table->dropColumn('default_pass_percent');
        });
    }
};
