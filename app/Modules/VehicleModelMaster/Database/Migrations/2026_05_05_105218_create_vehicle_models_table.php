<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicle_models', function (Blueprint $table) {
            $table->id();
            $table->foreignId('brand_id')->constrained('vehicle_brands')->restrictOnDelete();
            $table->string('name');
            $table->string('segment', 30)->nullable(); // hatchback | sedan | suv | muv | pickup | commercial
            $table->string('fuel_type', 20)->nullable(); // petrol | diesel | cng | electric | hybrid
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();

            // brand+name combo must be unique (Maruti Swift vs Nissan Swift)
            $table->unique(['brand_id', 'name']);
            $table->index(['is_active', 'name']);
            $table->index('segment');
            $table->index('fuel_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_models');
    }
};
