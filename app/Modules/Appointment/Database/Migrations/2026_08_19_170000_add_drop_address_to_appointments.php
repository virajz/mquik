<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Where the vehicle goes back to.
 *
 * Only a pickup address was captured, on the assumption the car returns where it
 * came from. It often does not — collected from home, returned to an office — so
 * the drop leg needs its own address, region and contact.
 *
 * `drop_region_id` is stored alongside the text: a typed one-off address still
 * needs a state/city/area for routing a driver, and a linked saved address
 * carries its own region already.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->text('drop_address')->nullable()->after('pickup_contact_phone');
            $table->foreignId('drop_address_id')->nullable()->after('drop_address')
                ->constrained('customer_addresses')->nullOnDelete();
            $table->foreignId('drop_region_id')->nullable()->after('drop_address_id')
                ->constrained('regions')->nullOnDelete();
            $table->string('drop_contact_phone', 20)->nullable()->after('drop_region_id');
        });
    }

    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('drop_address_id');
            $table->dropConstrainedForeignId('drop_region_id');
            $table->dropColumn(['drop_address', 'drop_contact_phone']);
        });
    }
};
