<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vehicle_variants', function (Blueprint $table) {
            $table->foreignId('fuel_type_id')->nullable()->after('fuel_type')
                ->constrained('fuel_types')->nullOnDelete();
            $table->foreignId('transmission_type_id')->nullable()->after('transmission')
                ->constrained('transmission_types')->nullOnDelete();
        });

        // Backfill the new FKs from the legacy enum strings (case-insensitive match).
        foreach (DB::table('vehicle_variants')->whereNotNull('fuel_type')->get(['id', 'fuel_type']) as $v) {
            $id = DB::table('fuel_types')->whereRaw('UPPER(name) = ?', [strtoupper((string) $v->fuel_type)])->value('id');
            if ($id) {
                DB::table('vehicle_variants')->where('id', $v->id)->update(['fuel_type_id' => $id]);
            }
        }
        foreach (DB::table('vehicle_variants')->whereNotNull('transmission')->get(['id', 'transmission']) as $v) {
            $id = DB::table('transmission_types')->whereRaw('UPPER(name) = ?', [strtoupper((string) $v->transmission)])->value('id');
            if ($id) {
                DB::table('vehicle_variants')->where('id', $v->id)->update(['transmission_type_id' => $id]);
            }
        }

        // Drop the legacy column indexes first so SQLite (tests) doesn't choke on
        // dangling indexes when it rebuilds the table to drop the columns.
        Schema::table('vehicle_variants', function (Blueprint $table) {
            $table->dropIndex('vehicle_variants_fuel_type_index');
            $table->dropIndex('vehicle_variants_transmission_index');
        });

        // Retire the legacy enum string columns now that data is migrated.
        Schema::table('vehicle_variants', function (Blueprint $table) {
            $table->dropColumn(['fuel_type', 'transmission']);
        });
    }

    public function down(): void
    {
        Schema::table('vehicle_variants', function (Blueprint $table) {
            $table->string('transmission', 20)->nullable();
            $table->string('fuel_type', 20)->nullable();
        });

        Schema::table('vehicle_variants', function (Blueprint $table) {
            $table->dropForeign(['fuel_type_id']);
            $table->dropForeign(['transmission_type_id']);
            $table->dropColumn(['fuel_type_id', 'transmission_type_id']);
        });
    }
};
