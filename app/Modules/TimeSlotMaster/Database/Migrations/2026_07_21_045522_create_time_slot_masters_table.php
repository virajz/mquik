<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('time_slots', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('code', 20)->nullable()->unique();
            $table->time('slot_start_time');
            $table->time('slot_end_time');
            // How many vehicles the workshop can take in this window. Enforced as a
            // soft warning on the appointment form — an advisor may still override.
            $table->unsignedSmallInteger('max_vehicles_per_slot')->default(5);
            // Optional changeover gap after the slot, in minutes.
            $table->unsignedSmallInteger('buffer_minutes')->default(0);
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();

            // Most common query: active slots in chronological order.
            $table->index(['is_active', 'slot_start_time']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('time_slots');
    }
};
