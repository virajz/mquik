<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A vehicle on the UNREGISTERED plate type has no registration number — it is
 * brand new or in for pre-delivery work, and the plate arrives later. The
 * column has to allow null for that.
 *
 * The unique index stays: Postgres allows many nulls in a unique column, so
 * unregistered vehicles do not collide with each other.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customer_vehicles', function (Blueprint $table) {
            $table->string('registration_no', 20)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('customer_vehicles', function (Blueprint $table) {
            $table->string('registration_no', 20)->nullable(false)->change();
        });
    }
};
