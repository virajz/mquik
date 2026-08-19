<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The trips a vehicle makes while it is still ours.
 *
 * A visit was modelled as one arrival and one departure, which does not survive
 * contact with a workshop: between inward and delivery the car goes out for a
 * trial run, to an outside-labour vendor, for fuel or to the RTO — and comes
 * back each time. Those are not the outward; the vehicle is still in our care
 * and still on the job card.
 *
 * The final departure stays on `gate_visits.exited_at`. Everything in between
 * lives here, so "where is the car right now" has an answer.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gate_visit_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gate_visit_id')->constrained('gate_visits')->cascadeOnDelete();
            $table->foreignId('job_card_id')->nullable()->constrained('job_cards')->nullOnDelete();

            $table->string('purpose', 30); // trial_run / outside_labour / fuel / rto / customer_request / other
            $table->foreignId('vendor_id')->nullable()->constrained('vendors')->nullOnDelete();
            $table->foreignId('driver_employee_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->foreignId('out_gate_id')->nullable()->constrained('gates')->nullOnDelete();
            $table->foreignId('in_gate_id')->nullable()->constrained('gates')->nullOnDelete();

            $table->dateTime('out_at');
            $table->dateTime('expected_back_at')->nullable(); // drives the overdue alert
            $table->dateTime('in_at')->nullable();            // null = still out

            $table->unsignedInteger('odometer_out')->nullable();
            $table->unsignedInteger('odometer_in')->nullable();

            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['gate_visit_id', 'out_at']);
            // "which vehicles are off-site right now"
            $table->index(['in_at', 'expected_back_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gate_visit_movements');
    }
};
