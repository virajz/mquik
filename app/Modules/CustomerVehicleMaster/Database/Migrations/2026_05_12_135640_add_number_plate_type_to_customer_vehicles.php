<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * `number_plate_type` differentiates plate categories — affects which mask
 * the form applies. Stored as a free string but constrained at the form
 * layer to: private | commercial | government | bh_series | military | other.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customer_vehicles', function (Blueprint $table) {
            $table->string('number_plate_type', 30)->default('private')->after('registration_no');
        });
    }

    public function down(): void
    {
        Schema::table('customer_vehicles', function (Blueprint $table) {
            $table->dropColumn('number_plate_type');
        });
    }
};
