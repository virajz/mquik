<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * What the customer asked us to note, kept apart from `notes`, which is the
 * workshop talking to itself. Mixing the two means anything printed for or read
 * back to the customer risks carrying an internal remark with it.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->text('customer_note')->nullable()->after('notes');
        });
    }

    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropColumn('customer_note');
        });
    }
};
