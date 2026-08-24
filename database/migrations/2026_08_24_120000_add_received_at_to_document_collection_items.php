<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Documents arrive one at a time, not as a batch — the RC book on Monday, the
 * policy on Thursday. Each item stamps its own moment when its status flips to
 * received; the header-level received pair goes away.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('document_collection_items', function (Blueprint $table) {
            $table->timestamp('received_at')->nullable()->after('status');
        });

        // Items already marked received inherit the header stamp (or the row's
        // own update time). Correlated subquery — portable across Postgres and
        // the SQLite the test suite runs on.
        DB::table('document_collection_items')
            ->where('status', 'received')
            ->whereNull('received_at')
            ->update([
                'received_at' => DB::raw('(select coalesce(c.received_at, c.updated_at) from document_collections c where c.id = document_collection_items.document_collection_id)'),
            ]);
    }

    public function down(): void
    {
        Schema::table('document_collection_items', function (Blueprint $table) {
            $table->dropColumn('received_at');
        });
    }
};
