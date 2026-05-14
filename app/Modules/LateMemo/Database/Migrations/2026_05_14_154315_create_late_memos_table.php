<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('late_memos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->date('memo_date');
            $table->unsignedSmallInteger('late_by_minutes')->default(0);
            $table->text('reason')->nullable();
            $table->string('status', 15)->default('issued');   // issued | acknowledged | waived
            $table->foreignId('issued_by_employee_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->dateTime('issued_at')->nullable();
            $table->text('decision_notes')->nullable();
            $table->timestamps();

            $table->index(['employee_id', 'memo_date']);
            $table->index('status');
            $table->index('memo_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('late_memos');
    }
};
