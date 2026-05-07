<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->foreignId('business_type_id')->nullable()->after('customer_type')
                ->constrained('business_types')->restrictOnDelete();
        });

        // Make sure each customer_type enum value has a row in business_types.
        $existingTypes = DB::table('customers')->whereNotNull('customer_type')->distinct()->pluck('customer_type');
        foreach ($existingTypes as $type) {
            DB::table('business_types')->insertOrIgnore([
                'name' => strtoupper($type),
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $map = DB::table('business_types')->pluck('id', 'name');

        DB::table('customers')->orderBy('id')->chunkById(500, function ($rows) use ($map) {
            foreach ($rows as $row) {
                $key = strtoupper($row->customer_type ?? 'WALKING');
                DB::table('customers')->where('id', $row->id)->update([
                    'business_type_id' => $map[$key] ?? null,
                ]);
            }
        });

        Schema::table('customers', function (Blueprint $table) {
            // Index must be dropped before the column on SQLite — Postgres tolerates either order.
            $table->dropIndex(['customer_type', 'name']);
            $table->dropColumn('customer_type');
            $table->index(['business_type_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->string('customer_type', 20)->default('walking')->after('name');
        });

        $map = DB::table('business_types')->pluck('name', 'id');

        DB::table('customers')->orderBy('id')->chunkById(500, function ($rows) use ($map) {
            foreach ($rows as $row) {
                DB::table('customers')->where('id', $row->id)->update([
                    'customer_type' => strtolower($map[$row->business_type_id] ?? 'WALKING'),
                ]);
            }
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->dropForeign(['business_type_id']);
            $table->dropIndex(['business_type_id', 'name']);
            $table->dropColumn('business_type_id');
        });
    }
};
