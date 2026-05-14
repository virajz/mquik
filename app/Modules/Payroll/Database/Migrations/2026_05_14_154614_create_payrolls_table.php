<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payrolls', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->unsignedSmallInteger('period_year');   // e.g. 2026
            $table->unsignedTinyInteger('period_month');   // 1-12
            $table->decimal('basic_amount', 12, 2)->default(0);
            $table->decimal('hra_amount', 12, 2)->default(0);
            $table->decimal('da_amount', 12, 2)->default(0);
            $table->decimal('allowances_amount', 12, 2)->default(0);   // overtime + bonus + other
            $table->decimal('deductions_amount', 12, 2)->default(0);   // pf + esi + advance + fines
            $table->decimal('gross_amount', 12, 2)->default(0);   // basic + hra + da + allowances
            $table->decimal('net_amount', 12, 2)->default(0);   // gross - deductions
            $table->date('payment_date')->nullable();
            $table->string('status', 12)->default('draft');   // draft | finalized | paid
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['employee_id', 'period_year', 'period_month'], 'payrolls_emp_year_month_unique');
            $table->index(['period_year', 'period_month']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payrolls');
    }
};
