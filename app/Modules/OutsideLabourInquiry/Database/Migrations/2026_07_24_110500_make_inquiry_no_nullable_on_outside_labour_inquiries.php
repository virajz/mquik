<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The inquiry number is stamped after insert (it needs the row id), so the
 * column must permit NULL on the initial insert. This forward-only migration
 * relaxes the constraint on databases that already ran the create migration
 * with a NOT NULL column.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('outside_labour_inquiries', function (Blueprint $table) {
            $table->string('inquiry_no')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('outside_labour_inquiries', function (Blueprint $table) {
            $table->string('inquiry_no')->nullable(false)->change();
        });
    }
};
