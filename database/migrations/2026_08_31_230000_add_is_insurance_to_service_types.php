<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Whether a service type is an insurance job.
 *
 * It was inferred from the name containing "INSURANCE", which quietly fails the
 * moment a workshop names one "CASHLESS CLAIM" or "TP REPAIR". The master should
 * state it rather than have the code guess.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('service_types', 'is_insurance')) {
            Schema::table('service_types', function (Blueprint $table) {
                $table->boolean('is_insurance')->default(false)->after('requires_advisor');
            });
        }

        // Carry over what the name-matching rule was already treating as insurance.
        DB::table('service_types')
            ->whereRaw('upper(name) like ?', ['%INSURANCE%'])
            ->update(['is_insurance' => true]);
    }

    public function down(): void
    {
        Schema::table('service_types', fn (Blueprint $table) => $table->dropColumn('is_insurance'));
    }
};
