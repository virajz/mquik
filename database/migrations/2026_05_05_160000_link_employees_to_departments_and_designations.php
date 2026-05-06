<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->foreignId('department_id')->nullable()->after('pan')
                ->constrained('departments')->restrictOnDelete();
            $table->foreignId('designation_id')->nullable()->after('department_id')
                ->constrained('designations')->restrictOnDelete();
        });

        $existingDeptNames = DB::table('employees')->whereNotNull('department')->distinct()->pluck('department');
        foreach ($existingDeptNames as $name) {
            DB::table('departments')->insertOrIgnore([
                'name' => $name,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
        $existingDesigNames = DB::table('employees')->whereNotNull('designation')->distinct()->pluck('designation');
        foreach ($existingDesigNames as $name) {
            DB::table('designations')->insertOrIgnore([
                'name' => $name,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $deptMap = DB::table('departments')->pluck('id', 'name');
        $desigMap = DB::table('designations')->pluck('id', 'name');

        DB::table('employees')->orderBy('id')->chunkById(500, function ($rows) use ($deptMap, $desigMap) {
            foreach ($rows as $row) {
                DB::table('employees')->where('id', $row->id)->update([
                    'department_id' => $deptMap[$row->department] ?? null,
                    'designation_id' => $desigMap[$row->designation] ?? null,
                ]);
            }
        });

        Schema::table('employees', function (Blueprint $table) {
            $table->dropIndex(['department', 'name']);
            $table->dropIndex(['designation', 'name']);
            $table->dropColumn(['department', 'designation']);

            $table->index(['department_id', 'name']);
            $table->index(['designation_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->string('department')->nullable()->after('pan');
            $table->string('designation')->nullable()->after('department');
        });

        $deptMap = DB::table('departments')->pluck('name', 'id');
        $desigMap = DB::table('designations')->pluck('name', 'id');

        DB::table('employees')->orderBy('id')->chunkById(500, function ($rows) use ($deptMap, $desigMap) {
            foreach ($rows as $row) {
                DB::table('employees')->where('id', $row->id)->update([
                    'department' => $deptMap[$row->department_id] ?? null,
                    'designation' => $desigMap[$row->designation_id] ?? null,
                ]);
            }
        });

        Schema::table('employees', function (Blueprint $table) {
            $table->dropForeign(['department_id']);
            $table->dropForeign(['designation_id']);
            $table->dropIndex(['department_id', 'name']);
            $table->dropIndex(['designation_id', 'name']);
            $table->dropColumn(['department_id', 'designation_id']);

            $table->index(['department', 'name']);
            $table->index(['designation', 'name']);
        });
    }
};
