<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('distance_slabs', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('code', 20)->nullable()->unique();
            // Inclusive band in kilometres; a null max_km means "and above".
            $table->unsignedSmallInteger('min_km');
            $table->unsignedSmallInteger('max_km')->nullable();
            $table->decimal('charge_amount', 10, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();

            // Most common query: active bands in distance order, and slab lookup by km.
            $table->index(['is_active', 'min_km']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('distance_slabs');
    }
};
