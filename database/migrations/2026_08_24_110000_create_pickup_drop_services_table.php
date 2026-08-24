<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The jobs a pickup/drop is booked for, mirroring `appointment_services`: a row
 * is either a real job description (ticked or picked) or a free-typed line,
 * kept apart from complaints, which stay the customer's own words.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pickup_drop_services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pickup_drop_id')->constrained()->cascadeOnDelete();
            $table->foreignId('job_description_id')->nullable()->constrained('job_descriptions')->nullOnDelete();
            $table->string('name');
            $table->unsignedSmallInteger('sequence_no')->default(0);
            $table->timestamps();

            $table->index(['pickup_drop_id', 'sequence_no']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pickup_drop_services');
    }
};
