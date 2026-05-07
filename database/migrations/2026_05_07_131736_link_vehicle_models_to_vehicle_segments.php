<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vehicle_models', function (Blueprint $table) {
            $table->foreignId('vehicle_segment_id')->nullable()->after('segment')
                ->constrained('vehicle_segments')->nullOnDelete();
        });

        $existingTypes = DB::table('vehicle_models')->whereNotNull('segment')->distinct()->pluck('segment');
        foreach ($existingTypes as $type) {
            DB::table('vehicle_segments')->insertOrIgnore([
                'name' => strtoupper($type),
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $map = DB::table('vehicle_segments')->pluck('id', 'name');

        DB::table('vehicle_models')->orderBy('id')->chunkById(500, function ($rows) use ($map) {
            foreach ($rows as $row) {
                if ($row->segment === null) {
                    continue;
                }
                DB::table('vehicle_models')->where('id', $row->id)->update([
                    'vehicle_segment_id' => $map[strtoupper($row->segment)] ?? null,
                ]);
            }
        });

        Schema::table('vehicle_models', function (Blueprint $table) {
            // Index must be dropped before the column on SQLite — Postgres tolerates either order.
            $table->dropIndex(['segment']);
            $table->dropColumn('segment');
            $table->index(['vehicle_segment_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::table('vehicle_models', function (Blueprint $table) {
            $table->string('segment', 30)->nullable()->after('vehicle_segment_id');
        });

        $map = DB::table('vehicle_segments')->pluck('name', 'id');

        DB::table('vehicle_models')->orderBy('id')->chunkById(500, function ($rows) use ($map) {
            foreach ($rows as $row) {
                if ($row->vehicle_segment_id === null) {
                    continue;
                }
                DB::table('vehicle_models')->where('id', $row->id)->update([
                    'segment' => strtolower($map[$row->vehicle_segment_id] ?? ''),
                ]);
            }
        });

        Schema::table('vehicle_models', function (Blueprint $table) {
            $table->dropForeign(['vehicle_segment_id']);
            $table->dropIndex(['vehicle_segment_id', 'name']);
            $table->dropColumn('vehicle_segment_id');
        });
    }
};
