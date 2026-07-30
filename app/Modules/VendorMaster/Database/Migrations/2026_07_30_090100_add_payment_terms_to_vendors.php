<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Module 97 — Vendor Master: add the `payment_terms` column.
 *
 * An earlier migration only ever re-added `payment_terms` inside its down(), so
 * the column never actually existed on the table. This adds it as a nullable
 * enum-backed string (Advance / Cash / Immediate / 7-60 Days).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vendors', function (Blueprint $table) {
            if (! Schema::hasColumn('vendors', 'payment_terms')) {
                $table->string('payment_terms', 20)->nullable()->after('credit_limit');
            }
        });
    }

    public function down(): void
    {
        Schema::table('vendors', function (Blueprint $table) {
            if (Schema::hasColumn('vendors', 'payment_terms')) {
                $table->dropColumn('payment_terms');
            }
        });
    }
};
