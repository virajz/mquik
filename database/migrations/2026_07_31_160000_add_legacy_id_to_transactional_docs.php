<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Track the old-ERP document number on imported estimates / proformas /
 * invoices, so the legacy transaction import is idempotent and the old
 * paperwork stays traceable. Line items are re-derived from the header's
 * legacy id in a second pass, so they need no legacy column of their own.
 */
return new class extends Migration
{
    /** @var list<string> */
    protected array $tables = ['sales_estimates', 'proformas', 'regular_sales_invoices'];

    public function up(): void
    {
        foreach ($this->tables as $t) {
            Schema::table($t, function (Blueprint $table) {
                $table->string('legacy_id', 40)->nullable()->unique()->after('id');
            });
        }
    }

    public function down(): void
    {
        foreach ($this->tables as $t) {
            Schema::table($t, function (Blueprint $table) {
                $table->dropUnique([$t.'_legacy_id_unique']);
                $table->dropColumn('legacy_id');
            });
        }
    }
};
