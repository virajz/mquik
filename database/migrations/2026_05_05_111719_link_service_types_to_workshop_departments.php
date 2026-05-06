<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('service_types', function (Blueprint $table) {
            $table->foreignId('workshop_department_id')->nullable()->after('code')
                ->constrained('workshop_departments')->restrictOnDelete();
        });

        $existingNames = DB::table('service_types')->whereNotNull('department')->distinct()->pluck('department');
        foreach ($existingNames as $name) {
            DB::table('workshop_departments')->insertOrIgnore([
                'name' => strtoupper($name),
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $map = DB::table('workshop_departments')->pluck('id', 'name');

        DB::table('service_types')->orderBy('id')->chunkById(500, function ($rows) use ($map) {
            foreach ($rows as $row) {
                DB::table('service_types')->where('id', $row->id)->update([
                    'workshop_department_id' => $map[strtoupper($row->department)] ?? null,
                ]);
            }
        });

        Schema::table('service_types', function (Blueprint $table) {
            $table->dropIndex(['department', 'name']);
            $table->dropColumn('department');

            $table->index(['workshop_department_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::table('service_types', function (Blueprint $table) {
            $table->string('department')->nullable()->after('code');
        });

        $map = DB::table('workshop_departments')->pluck('name', 'id');

        DB::table('service_types')->orderBy('id')->chunkById(500, function ($rows) use ($map) {
            foreach ($rows as $row) {
                DB::table('service_types')->where('id', $row->id)->update([
                    'department' => $map[$row->workshop_department_id] ?? null,
                ]);
            }
        });

        Schema::table('service_types', function (Blueprint $table) {
            $table->dropForeign(['workshop_department_id']);
            $table->dropIndex(['workshop_department_id', 'name']);
            $table->dropColumn('workshop_department_id');

            $table->index(['department', 'name']);
        });
    }
};
