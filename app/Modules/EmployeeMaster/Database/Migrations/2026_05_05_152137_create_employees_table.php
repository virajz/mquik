<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->string('employee_code', 30)->unique();
            $table->string('name');
            $table->string('gender', 10)->nullable();           // male | female | other
            $table->date('date_of_birth')->nullable();
            $table->string('phone', 20);
            $table->string('alternate_phone', 20)->nullable();
            $table->string('email')->nullable();
            $table->text('address')->nullable();
            $table->string('city')->nullable();
            $table->char('pincode', 6)->nullable();
            $table->char('aadhar', 12)->nullable()->unique();
            $table->char('pan', 10)->nullable()->unique();
            // Employment
            $table->string('designation', 50);                  // Advisor / Floor Incharge / Technician / Cashier / etc.
            $table->string('department', 50);                   // Service / Bodyshop / Tyre / Stores / Accounts / HR / Admin
            $table->date('joining_date');
            $table->date('exit_date')->nullable();
            // Banking
            $table->string('bank_name')->nullable();
            $table->string('bank_branch')->nullable();
            $table->char('ifsc', 11)->nullable();
            $table->string('account_no', 30)->nullable();
            // Status
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('name');
            $table->index('phone');
            $table->index(['designation', 'name']);
            $table->index(['department', 'name']);
            $table->index(['is_active', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
