<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('salary_structures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->date('effective_from');
            $table->string('status', 20)->default('draft'); // draft | active | superseded
            $table->decimal('basic_salary', 14, 2)->default(0);
            $table->decimal('gross_earnings', 14, 2)->default(0);
            $table->decimal('total_deductions', 14, 2)->default(0);
            $table->decimal('net_salary', 14, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['employee_id', 'effective_from']);
            $table->index(['status']);
        });

        Schema::create('salary_structure_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('salary_structure_id')->constrained('salary_structures')->cascadeOnDelete();
            $table->foreignId('salary_component_id')->nullable()->constrained('salary_components')->nullOnDelete();
            $table->string('component_type', 20);  // earning | deduction (snapshot)
            $table->string('calc_method', 20);      // fixed | percent_of_basic (snapshot)
            $table->decimal('value', 14, 2)->default(0);  // amount, or percent per calc_method
            $table->decimal('amount', 14, 2)->default(0); // computed monetary amount
            $table->unsignedInteger('sequence_no')->default(0);
            $table->timestamps();

            $table->index(['salary_structure_id', 'sequence_no']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('salary_structure_lines');
        Schema::dropIfExists('salary_structures');
    }
};
