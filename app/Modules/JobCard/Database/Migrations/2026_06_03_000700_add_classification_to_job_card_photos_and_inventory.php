<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Photos gain full damage-mapping: what the photo shows (photo type),
        // the nature of any damage (damage type), and where on the vehicle.
        Schema::table('job_card_photos', function (Blueprint $table) {
            $table->foreignId('photo_type_id')->nullable()->after('job_card_id')
                ->constrained('photo_types')->nullOnDelete();
            $table->foreignId('damage_type_id')->nullable()->after('photo_type_id')
                ->constrained('damage_types')->nullOnDelete();
            $table->string('location_note', 120)->nullable()->after('caption');

            $table->index('photo_type_id');
            $table->index('damage_type_id');
        });

        // Inventory items become tri-state (present | missing | damaged) and a
        // damaged item can be tagged with a damage type. is_present is retained
        // as a derived convenience flag (status === 'present').
        Schema::table('job_card_inventory_items', function (Blueprint $table) {
            $table->string('status', 12)->default('present')->after('vehicle_inventory_item_id');
            $table->foreignId('damage_type_id')->nullable()->after('is_present')
                ->constrained('damage_types')->nullOnDelete();

            $table->index(['job_card_id', 'status']);
        });

        // Backfill status from the legacy boolean: present rows stay present,
        // the rest were only ever stored when flagged, so treat as missing.
        DB::table('job_card_inventory_items')->where('is_present', true)->update(['status' => 'present']);
        DB::table('job_card_inventory_items')->where('is_present', false)->update(['status' => 'missing']);
    }

    public function down(): void
    {
        Schema::table('job_card_photos', function (Blueprint $table) {
            $table->dropForeign(['photo_type_id']);
            $table->dropForeign(['damage_type_id']);
            $table->dropColumn(['photo_type_id', 'damage_type_id', 'location_note']);
        });

        Schema::table('job_card_inventory_items', function (Blueprint $table) {
            $table->dropForeign(['damage_type_id']);
            $table->dropColumn(['status', 'damage_type_id']);
        });
    }
};
