<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leave_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->string('leave_type', 20);   // CL | SL | PL | COMP_OFF | UNPAID
            $table->date('from_date');
            $table->date('to_date');
            $table->decimal('days_count', 4, 1);   // supports half-days
            $table->text('reason')->nullable();
            $table->string('status', 12)->default('pending');   // pending | approved | rejected | cancelled
            $table->foreignId('approved_by_employee_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->dateTime('approved_at')->nullable();
            $table->text('decision_notes')->nullable();
            $table->timestamps();

            $table->index(['employee_id', 'status']);
            $table->index(['from_date', 'to_date']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leave_requests');
    }
};
