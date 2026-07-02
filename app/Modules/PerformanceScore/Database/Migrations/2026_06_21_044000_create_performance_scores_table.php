<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('performance_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->unsignedSmallInteger('period_year');
            $table->unsignedTinyInteger('period_month');
            $table->decimal('total_positive', 10, 2)->default(0);
            $table->decimal('total_negative', 10, 2)->default(0);
            $table->decimal('net_points', 10, 2)->default(0);
            $table->decimal('max_points', 10, 2)->default(0);
            $table->decimal('achievement_percent', 6, 2)->default(0);
            $table->foreignId('performance_slab_id')->nullable()->constrained('performance_slabs')->nullOnDelete();
            $table->decimal('incentive_amount', 12, 2)->default(0);
            $table->string('status', 20)->default('draft'); // draft | finalized
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['employee_id', 'period_year', 'period_month'], 'perf_scores_emp_year_month_unique');
            $table->index(['period_year', 'period_month']);
            $table->index('status');
        });

        Schema::create('performance_score_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('performance_score_id')->constrained('performance_scores')->cascadeOnDelete();
            $table->foreignId('smart_salary_kpi_id')->nullable()->constrained('smart_salary_kpis')->nullOnDelete();
            $table->string('polarity', 20);  // positive | negative (snapshot)
            $table->decimal('max_points', 10, 2)->default(0);       // snapshot of the KPI weight
            $table->decimal('points_awarded', 10, 2)->default(0);   // achieved / incurred this period
            $table->string('notes')->nullable();
            $table->unsignedInteger('sequence_no')->default(0);
            $table->timestamps();

            $table->index(['performance_score_id', 'sequence_no']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('performance_score_lines');
        Schema::dropIfExists('performance_scores');
    }
};
