<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * One department list to maintain, not two.
 *
 * `workshop_departments` (SERVICE, BODYSHOP, TYRE …) and `departments` (the HR
 * list, which also holds ACCOUNTS, STORES, HR …) were separate, overlapping by
 * name only. That meant staff could not be matched to the department on a job
 * card except by string comparison, and adding a workshop department left HR
 * unaware of it.
 *
 * Both tables stay — dozens of modules point at each — but a workshop department
 * now *owns* a link to its HR counterpart, backfilled by name here and kept in
 * step by the model from now on. The HR list remains the superset: it keeps the
 * back-office departments that never appear on a job card.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('workshop_departments', function (Blueprint $table) {
            $table->foreignId('department_id')->nullable()->after('name')
                ->constrained('departments')->nullOnDelete();
        });

        foreach (DB::table('workshop_departments')->get() as $wd) {
            $name = mb_strtoupper(trim($wd->name));

            $departmentId = DB::table('departments')->whereRaw('upper(name) = ?', [$name])->value('id')
                ?? DB::table('departments')->insertGetId([
                    'name' => $name,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

            DB::table('workshop_departments')->where('id', $wd->id)->update(['department_id' => $departmentId]);
        }
    }

    public function down(): void
    {
        Schema::table('workshop_departments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('department_id');
        });
    }
};
