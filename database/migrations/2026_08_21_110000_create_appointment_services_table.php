<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * What the customer is booking in for, as distinct from what they complained
 * about. Ticking a job on the booking screen should not manufacture a customer
 * complaint — the complaint is what they said in their own words, and these are
 * the jobs we agreed to do.
 *
 * A row is either a real job description (ticked, or picked from the Requested
 * Repairs dropdown) or a free-typed line for something the master does not have
 * yet, which is why `job_description_id` is nullable and `name` always holds the
 * label either way.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('appointment_services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('appointment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('job_description_id')->nullable()->constrained('job_descriptions')->nullOnDelete();
            $table->string('name');
            $table->unsignedSmallInteger('sequence_no')->default(0);
            $table->timestamps();

            $table->index(['appointment_id', 'sequence_no']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appointment_services');
    }
};
