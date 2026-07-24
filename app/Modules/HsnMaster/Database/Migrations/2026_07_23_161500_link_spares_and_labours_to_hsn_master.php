<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Point the two catalogue records at the HSN master.
 *
 * Transactions deliberately keep their free-text `hsn_code`: an invoice line
 * must freeze the code as it stood at billing time, not follow later edits to
 * the master. Only the catalogue — where the code is a current property of the
 * item — moves to a foreign key.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('spares', function (Blueprint $table) {
            $table->foreignId('hsn_id')->nullable()->after('hsn_code')
                ->constrained('hsn_codes')->nullOnDelete();
        });

        Schema::table('labours', function (Blueprint $table) {
            $table->foreignId('hsn_id')->nullable()->after('hsn_sac_code')
                ->constrained('hsn_codes')->nullOnDelete();
        });

        $this->backfill('spares', 'hsn_code');
        $this->backfill('labours', 'hsn_sac_code');

        // SQLite will not drop a column an index still references, so both
        // indexes have to go first. Postgres cascades; the test suite does not.
        Schema::table('spares', function (Blueprint $table) {
            $table->dropIndex('spares_hsn_code_index');
            $table->dropColumn('hsn_code');
        });

        Schema::table('labours', function (Blueprint $table) {
            $table->dropIndex('labours_hsn_sac_code_index');
            $table->dropColumn('hsn_sac_code');
        });
    }

    /**
     * Match existing free-text codes to master rows. Anything unmatched is left
     * null rather than invented — a wrong HSN is a tax problem, not a cosmetic one.
     */
    private function backfill(string $table, string $column): void
    {
        foreach (DB::table('hsn_codes')->pluck('id', 'code') as $code => $id) {
            DB::table($table)->where($column, (string) $code)->update(['hsn_id' => $id]);
        }
    }

    public function down(): void
    {
        Schema::table('spares', function (Blueprint $table) {
            $table->string('hsn_code', 16)->nullable();
            $table->dropConstrainedForeignId('hsn_id');
        });

        Schema::table('labours', function (Blueprint $table) {
            $table->string('hsn_sac_code', 16)->nullable();
            $table->index('hsn_sac_code');
            $table->dropConstrainedForeignId('hsn_id');
        });
    }
};
