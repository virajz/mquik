<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Provenance column for the previous-ERP data migration (MasterDataSeeder Tier 3).
 * Nullable + indexed — lets the import be idempotent (firstOrCreate/skip by legacy_id)
 * and traceable back to the old Site / SiteVeh rows.
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (['customers', 'employees', 'vendors', 'customer_vehicles'] as $table) {
            Schema::table($table, function (Blueprint $t) {
                $t->unsignedBigInteger('legacy_id')->nullable()->index();
            });
        }
    }

    public function down(): void
    {
        foreach (['customers', 'employees', 'vendors', 'customer_vehicles'] as $table) {
            Schema::table($table, function (Blueprint $t) {
                $t->dropColumn('legacy_id');
            });
        }
    }
};
