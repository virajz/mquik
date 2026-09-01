<?php

use App\Support\FinancialYear;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Inspections join the FY-aware series: MQ/VI/26-27/00001.
 *
 * Also records which bay the car was inspected in. Nothing linked an inspection
 * to a bay before — the listing was asked to report Bay No. and had nowhere to
 * read it from.
 *
 * Re-runnable like the other series migrations: a sheet already carrying the new
 * form keeps its number, and the next sequence per financial year is read from
 * what is already there rather than from a count, which would collide the moment
 * the run has a gap.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('digital_inspections', function (Blueprint $table) {
            if (! Schema::hasColumn('digital_inspections', 'fy_label')) {
                $table->string('fy_label', 5)->nullable()->after('inspection_no');
            }

            if (! Schema::hasColumn('digital_inspections', 'bay_id')) {
                $table->foreignId('bay_id')->nullable()->after('advisor_id')
                    ->constrained('bays')->nullOnDelete();
            }
        });

        DB::statement(<<<'SQL'
            update digital_inspections
            set fy_label = substr(inspection_no, 7, 5)
            where inspection_no like 'MQ/VI/%' and fy_label is null
        SQL);

        $next = DB::table('digital_inspections')
            ->where('inspection_no', 'like', 'MQ/VI/%')
            ->selectRaw('substr(inspection_no, 7, 5) as fy, max(cast(substr(inspection_no, 13) as integer)) as top')
            ->groupByRaw('substr(inspection_no, 7, 5)')
            ->pluck('top', 'fy')
            ->map(fn ($top) => (int) $top)
            ->all();

        DB::table('digital_inspections')
            ->where(fn ($q) => $q->whereNull('inspection_no')->orWhere('inspection_no', 'not like', 'MQ/VI/%'))
            ->orderBy('id')
            ->get(['id', 'created_at'])
            ->each(function ($row) use (&$next) {
                $fy = FinancialYear::label($row->created_at);
                $next[$fy] = ($next[$fy] ?? 0) + 1;

                DB::table('digital_inspections')->where('id', $row->id)->update([
                    'fy_label' => $fy,
                    'inspection_no' => 'MQ/VI/'.$fy.'/'.str_pad((string) $next[$fy], 5, '0', STR_PAD_LEFT),
                ]);
            });
    }

    public function down(): void
    {
        Schema::table('digital_inspections', function (Blueprint $table) {
            $table->dropConstrainedForeignId('bay_id');
            $table->dropColumn('fy_label');
        });
    }
};
