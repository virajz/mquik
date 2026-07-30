<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add the customer's GST number.
 *
 * The customer master already carried a GST *type* (`gst_type_id`) but never a
 * GST *number*. This adds `gstin` — the 15-char GSTIN — alongside it.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->string('gstin', 15)->nullable()->unique()->after('gst_type_id');
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropUnique(['gstin']);
            $table->dropColumn('gstin');
        });
    }
};
