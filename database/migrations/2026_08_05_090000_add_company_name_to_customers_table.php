<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A first-class company name on the customer.
 *
 * Until now the guidance was "for company customers, put the full company name
 * in First Name" — which made a company indistinguishable from a person, broke
 * name-part search, and left GST-registered customers with no field that
 * matches what is printed on their invoice.
 *
 * Indexed because it is searchable; the trigram index for fuzzy search is
 * created by `php artisan auth:sync-search-indexes`.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->string('company_name')->nullable()->after('last_name');
            $table->index('company_name');
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropIndex(['company_name']);
            $table->dropColumn('company_name');
        });
    }
};
