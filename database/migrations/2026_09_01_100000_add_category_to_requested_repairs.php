<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Frequent or general, exactly as job descriptions already work.
 *
 * "Frequent" earns a checkbox on the job card; "general" lives in the
 * search-and-pick box underneath. Everything starts general — a complaint is
 * promoted to frequent deliberately, not by accident of import.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('requested_repairs', 'category')) {
            Schema::table('requested_repairs', function (Blueprint $table) {
                $table->string('category', 20)->default('general')->after('name');
                $table->index(['category', 'is_active']);
            });
        }
    }

    public function down(): void
    {
        Schema::table('requested_repairs', function (Blueprint $table) {
            $table->dropIndex(['category', 'is_active']);
            $table->dropColumn('category');
        });
    }
};
