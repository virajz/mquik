<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('job_descriptions', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code', 20)->nullable();
            $table->string('category', 20); // frequent | general
            $table->foreignId('service_type_id')->nullable()->constrained('service_types')->nullOnDelete();
            $table->decimal('standard_hours', 6, 2)->nullable();
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['service_type_id', 'name']);
            $table->index(['category', 'name']);
            $table->index(['service_type_id', 'name']);
            $table->index(['is_active', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('job_descriptions');
    }
};
