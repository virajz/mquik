<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('smart_salary_kpis', function (Blueprint $table) {
            $table->string('polarity', 20)->default('positive')->after('category'); // positive | negative
            $table->index(['polarity', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::table('smart_salary_kpis', function (Blueprint $table) {
            $table->dropIndex(['polarity', 'is_active']);
            $table->dropColumn('polarity');
        });
    }
};
