<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payrolls', function (Blueprint $table) {
            // Performance incentive fed from the Smart Salary engine; part of gross.
            $table->decimal('incentive_amount', 12, 2)->default(0)->after('allowances_amount');
        });
    }

    public function down(): void
    {
        Schema::table('payrolls', function (Blueprint $table) {
            $table->dropColumn('incentive_amount');
        });
    }
};
