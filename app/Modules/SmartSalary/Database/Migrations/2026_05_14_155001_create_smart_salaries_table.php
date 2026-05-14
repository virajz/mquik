<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('smart_salary_kpis', function (Blueprint $table) {
            $table->id();
            $table->string('key', 64);     // e.g. ATTENDANCE_PCT, BILLING_RATIO, REPEAT_JOB
            $table->string('name');
            $table->string('category', 40);   // e.g. Attendance, Performance, Sales, Quality, Discipline
            $table->string('direction', 20)->default('higher_is_better');   // higher_is_better | lower_is_better
            $table->string('unit', 20)->nullable();   // %, count, ₹, days
            $table->decimal('weight', 6, 2)->default(0);   // contribution to total score
            $table->text('formula')->nullable();   // placeholder — actual logic lands Wk 7
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique('key');
            $table->index('category');
            $table->index(['is_active', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('smart_salary_kpis');
    }
};
