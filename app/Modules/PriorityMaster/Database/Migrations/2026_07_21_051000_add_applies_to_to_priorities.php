<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Workshop priorities (Normal/High/Urgent) and parts-supply priorities
 * (…/Breakdown/Critical) share one list but not one vocabulary. `applies_to`
 * scopes each value so a module's dropdown only offers what makes sense for it.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('priorities', function (Blueprint $table) {
            $table->string('applies_to', 10)->default('both')->after('code'); // workshop | parts | both
            $table->index(['applies_to', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::table('priorities', function (Blueprint $table) {
            $table->dropIndex(['applies_to', 'sort_order']);
            $table->dropColumn('applies_to');
        });
    }
};
