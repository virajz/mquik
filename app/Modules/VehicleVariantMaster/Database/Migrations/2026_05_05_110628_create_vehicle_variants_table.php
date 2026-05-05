<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicle_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('model_id')->constrained('vehicle_models')->restrictOnDelete();
            $table->string('name');
            $table->string('transmission', 20)->nullable(); // manual | automatic | amt | cvt | dct
            $table->string('engine_cc', 20)->nullable();
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['model_id', 'name']);
            $table->index(['is_active', 'name']);
            $table->index('transmission');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_variants');
    }
};
