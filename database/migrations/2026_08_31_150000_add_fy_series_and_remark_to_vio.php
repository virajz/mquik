<?php

use App\Support\FinancialYear;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Work orders join the FY-aware series: MQ/VIO/26-27/00001.
 *
 * Also adds the technician's closing remark, which belongs to the order rather
 * than to any one line — it is what they say about the job as a whole.
 *
 * Re-runnable in the same way as the job-card series: an order already carrying
 * the new form keeps its number, and the next sequence per financial year is
 * read from what is already there rather than from a count, which would collide
 * the moment the run has a gap.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vehicle_inspection_orders', function (Blueprint $table) {
            if (! Schema::hasColumn('vehicle_inspection_orders', 'fy_label')) {
                $table->string('fy_label', 5)->nullable()->after('order_no');
            }

            if (! Schema::hasColumn('vehicle_inspection_orders', 'technician_remark')) {
                $table->text('technician_remark')->nullable()->after('notes');
            }
        });

        DB::statement(<<<'SQL'
            update vehicle_inspection_orders
            set fy_label = split_part(order_no, '/', 3)
            where order_no like 'MQ/VIO/%' and fy_label is null
        SQL);

        $next = DB::table('vehicle_inspection_orders')
            ->where('order_no', 'like', 'MQ/VIO/%')
            ->selectRaw("split_part(order_no, '/', 3) as fy, max(cast(split_part(order_no, '/', 4) as integer)) as top")
            ->groupBy('fy')
            ->pluck('top', 'fy')
            ->map(fn ($top) => (int) $top)
            ->all();

        DB::table('vehicle_inspection_orders')
            ->where(fn ($q) => $q->whereNull('order_no')->orWhere('order_no', 'not like', 'MQ/VIO/%'))
            ->orderBy('id')
            ->get(['id', 'ordered_at', 'created_at'])
            ->each(function ($order) use (&$next) {
                $fy = FinancialYear::label($order->ordered_at ?? $order->created_at);
                $next[$fy] = ($next[$fy] ?? 0) + 1;

                DB::table('vehicle_inspection_orders')->where('id', $order->id)->update([
                    'fy_label' => $fy,
                    'order_no' => 'MQ/VIO/'.$fy.'/'.str_pad((string) $next[$fy], 5, '0', STR_PAD_LEFT),
                ]);
            });
    }

    public function down(): void
    {
        Schema::table('vehicle_inspection_orders', function (Blueprint $table) {
            $table->dropColumn(['fy_label', 'technician_remark']);
        });
    }
};
