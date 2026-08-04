<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The old-ERP spare export carries no primary key of its own — a spare is
 * identified by part no + name (or, when the part no is blank, by name +
 * brand + HSN + sub-group). This column stores a hash of that natural key so
 * the import is idempotent and rate-history rows can be matched back to their
 * spare without relying on insert order.
 *
 * A string key rather than the `legacy_id` bigint used on customers/vehicles,
 * because the source has an identifying tuple, not an id.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('spares', function (Blueprint $table) {
            $table->string('legacy_key', 64)->nullable()->unique()->after('id');
        });
    }

    public function down(): void
    {
        Schema::table('spares', function (Blueprint $table) {
            $table->dropUnique('spares_legacy_key_unique');
            $table->dropColumn('legacy_key');
        });
    }
};
