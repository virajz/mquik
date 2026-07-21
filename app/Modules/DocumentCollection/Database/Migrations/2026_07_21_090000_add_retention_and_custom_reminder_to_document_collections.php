<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Row 8 remainder — the two options that existed as labels but had nothing
 * behind them:
 *
 *  - "Custom" reminder frequency had no interval to be custom about.
 *  - "Delete" retention had no day count and nothing that ever ran.
 *
 * Retention is a soft delete: the record and its checklist history survive for
 * audit while the collection drops out of every normal query. The clock starts
 * at the linked job card's closed_at until Document Collection gains a real
 * invoice link, at which point only the anchor changes.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('document_collections', function (Blueprint $table) {
            $table->unsignedSmallInteger('reminder_custom_days')->nullable()->after('reminder_frequency');
            $table->unsignedSmallInteger('retention_days')->nullable()->after('retention');
            $table->dateTime('retired_at')->nullable()->after('retention_days');
            $table->softDeletes();

            $table->index(['retention', 'retired_at']);
        });

        Schema::table('document_collection_items', function (Blueprint $table) {
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('document_collection_items', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('document_collections', function (Blueprint $table) {
            $table->dropIndex(['retention', 'retired_at']);
            $table->dropSoftDeletes();
            $table->dropColumn(['reminder_custom_days', 'retention_days', 'retired_at']);
        });
    }
};
