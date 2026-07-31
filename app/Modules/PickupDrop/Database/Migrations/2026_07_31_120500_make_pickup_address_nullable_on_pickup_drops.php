<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * `pickup_address` was NOT NULL (a legacy from the original `address` column).
 * It is optional now: a drop-only job has no pickup address, and a pickup that
 * uses a saved address stores the link (`pickup_address_id`) with no text.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pickup_drops', function (Blueprint $table) {
            $table->text('pickup_address')->nullable()->change();
        });
    }

    public function down(): void
    {
        // Backfill any nulls so the NOT NULL restore doesn't fail, then restore.
        DB::table('pickup_drops')->whereNull('pickup_address')->update(['pickup_address' => '']);

        Schema::table('pickup_drops', function (Blueprint $table) {
            $table->text('pickup_address')->nullable(false)->change();
        });
    }
};
