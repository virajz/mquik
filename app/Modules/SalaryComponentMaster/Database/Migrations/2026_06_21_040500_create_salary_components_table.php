<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('salary_components', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('code', 20)->nullable()->unique();
            $table->string('component_type', 20)->default('earning'); // earning | deduction
            $table->string('calc_method', 20)->default('fixed');      // fixed | percent_of_basic
            $table->decimal('default_value', 12, 2)->default(0);      // amount or percent per calc_method
            $table->boolean('is_taxable')->default(false);
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['is_active', 'name']);
            $table->index(['component_type', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('salary_components');
    }
};
