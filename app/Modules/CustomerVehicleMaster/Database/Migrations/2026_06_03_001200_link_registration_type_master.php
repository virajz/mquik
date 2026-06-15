<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customer_vehicles', function (Blueprint $table) {
            $table->foreignId('registration_type_id')->nullable()->after('number_plate_type')
                ->constrained('registration_types')->nullOnDelete();
        });

        // Backfill from the legacy enum. Find-or-create the master row only for
        // plate-type values actually present, so a fresh DB (tests) stays empty.
        foreach (DB::table('customer_vehicles')->whereNotNull('number_plate_type')->distinct()->pluck('number_plate_type') as $value) {
            $name = strtoupper(str_replace('_', ' ', (string) $value));
            $id = DB::table('registration_types')->where('name', $name)->value('id');
            if (! $id) {
                $id = DB::table('registration_types')->insertGetId([
                    'name' => $name, 'is_active' => true, 'created_at' => now(), 'updated_at' => now(),
                ]);
            }
            DB::table('customer_vehicles')->where('number_plate_type', $value)->update(['registration_type_id' => $id]);
        }

        Schema::table('customer_vehicles', function (Blueprint $table) {
            $table->dropColumn('number_plate_type');
        });
    }

    public function down(): void
    {
        Schema::table('customer_vehicles', function (Blueprint $table) {
            $table->string('number_plate_type', 30)->default('private')->after('registration_no');
        });

        Schema::table('customer_vehicles', function (Blueprint $table) {
            $table->dropForeign(['registration_type_id']);
            $table->dropColumn('registration_type_id');
        });
    }
};
