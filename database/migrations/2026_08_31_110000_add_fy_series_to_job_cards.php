<?php

use App\Support\FinancialYear;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Job cards join the FY-aware series the invoices and users already use:
 * MQ/JC/26-27/0001.
 *
 * Written to be re-runnable: a card that already carries the new form keeps its
 * number (renumbering twice would break every reference to it), and the next
 * sequence per financial year is read from what is already there.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('job_cards', 'fy_label')) {
            Schema::table('job_cards', function (Blueprint $table) {
                $table->string('fy_label', 5)->nullable()->after('job_card_no');
            });
        }

        // Cards already in the new form: recover their FY from the number itself.
        DB::statement(<<<'SQL'
            update job_cards
            set fy_label = substr(job_card_no, 7, 5)
            where job_card_no like 'MQ/JC/%' and fy_label is null
        SQL);

        // Highest sequence issued per FY, so stragglers continue the run.
        $next = DB::table('job_cards')
            ->where('job_card_no', 'like', 'MQ/JC/%')
            ->selectRaw('substr(job_card_no, 7, 5) as fy, max(cast(substr(job_card_no, 13) as integer)) as top')
            ->groupByRaw('substr(job_card_no, 7, 5)')
            ->pluck('top', 'fy')
            ->map(fn ($top) => (int) $top)
            ->all();

        DB::table('job_cards')
            ->where(fn ($q) => $q->whereNull('job_card_no')->orWhere('job_card_no', 'not like', 'MQ/JC/%'))
            ->orderBy('id')
            ->get(['id', 'opened_at', 'created_at'])
            ->each(function ($card) use (&$next) {
                $fy = FinancialYear::label($card->opened_at ?? $card->created_at);
                $next[$fy] = ($next[$fy] ?? 0) + 1;

                DB::table('job_cards')->where('id', $card->id)->update([
                    'fy_label' => $fy,
                    'job_card_no' => 'MQ/JC/'.$fy.'/'.str_pad((string) $next[$fy], 4, '0', STR_PAD_LEFT),
                ]);
            });
    }

    public function down(): void
    {
        Schema::table('job_cards', function (Blueprint $table) {
            $table->dropColumn('fy_label');
        });
    }
};
