<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pickup_drop_options', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('code', 20)->nullable()->unique();
            // Whether choosing this option means the workshop must run a Pickup
            // and/or a Drop job. Data-driven so the Appointment → Pickup/Drop
            // handoff never has to hardcode option codes.
            $table->boolean('involves_pickup')->default(false);
            $table->boolean('involves_drop')->default(false);
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();

            // Composite index for the most common query: active options, sorted by name
            $table->index(['is_active', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pickup_drop_options');
    }
};
