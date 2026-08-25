<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * One visit can span departments — a service plus a tyre swap plus detailing.
 * The booking keeps `workshop_department_id` as its primary (everything
 * downstream reads it: job-card handoff, routing, filters); this pivot carries
 * the full selection, and the quick-service checklist draws from all of them.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('appointment_workshop_department', function (Blueprint $table) {
            $table->id();
            $table->foreignId('appointment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('workshop_department_id')->constrained('workshop_departments')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['appointment_id', 'workshop_department_id']);
        });

        // Existing bookings: the single department becomes the selection.
        DB::table('appointments')->whereNotNull('workshop_department_id')->orderBy('id')
            ->each(function ($a) {
                DB::table('appointment_workshop_department')->insertOrIgnore([
                    'appointment_id' => $a->id,
                    'workshop_department_id' => $a->workshop_department_id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('appointment_workshop_department');
    }
};
