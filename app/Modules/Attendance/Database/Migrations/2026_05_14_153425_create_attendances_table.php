<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->dateTime('punched_at');
            $table->string('type', 3);  // in | out
            $table->string('selfie_path')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('punched_at');
            $table->index(['employee_id', 'punched_at']);
            $table->index(['type', 'punched_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};
