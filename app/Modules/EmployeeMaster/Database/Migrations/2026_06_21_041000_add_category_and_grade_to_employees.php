<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->foreignId('employee_category_id')->nullable()->after('department_id')->constrained('employee_categories')->nullOnDelete();
            $table->foreignId('employee_grade_id')->nullable()->after('employee_category_id')->constrained('employee_grades')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropConstrainedForeignId('employee_category_id');
            $table->dropConstrainedForeignId('employee_grade_id');
        });
    }
};
