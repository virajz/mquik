<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Queue Management System — a live service queue / display board for quick
 * services (car wash, alignment/balancing, PDI, AC service, detailing). Each
 * row is one vehicle in a queue, with FIFO/priority ordering, high-priority
 * approval, timestamps for waiting/washing/TAT, and reason enums.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_queues', function (Blueprint $table) {
            $table->id();
            $table->string('queue_no')->nullable()->unique();

            $table->string('queue_type', 30)->nullable(); // ac_service / alignment_balancing / pdi / car_wash / detailing
            $table->foreignId('job_card_id')->nullable()->constrained('job_cards')->nullOnDelete();
            $table->foreignId('customer_vehicle_id')->nullable()->constrained('customer_vehicles')->nullOnDelete();
            $table->foreignId('labour_id')->nullable()->constrained('labours')->nullOnDelete(); // the service performed
            $table->foreignId('technician_id')->nullable()->constrained('employees')->nullOnDelete();

            $table->string('job_description')->nullable();
            $table->string('ordering_mode', 15)->default('fifo'); // fifo / priority
            $table->string('screen_view', 15)->default('upcoming'); // upcoming / arrived / ready
            $table->string('status', 20)->default('waiting'); // waiting / in_progress / on_hold / ready / completed / cancelled

            // High-priority handling.
            $table->boolean('is_high_priority')->default(false);
            $table->string('high_priority_reason', 30)->nullable();
            $table->foreignId('hp_requested_by_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->foreignId('hp_requested_to_id')->nullable()->constrained('employees')->nullOnDelete();

            $table->string('rework_reason', 30)->nullable();
            $table->string('delay_reason', 30)->nullable();
            $table->string('pause_reason', 30)->nullable();

            // Timestamps for the board + KPIs.
            $table->dateTime('promised_delivery_at')->nullable();
            $table->dateTime('expected_completion_at')->nullable();
            $table->dateTime('kept_at')->nullable();    // vehicle kept for service
            $table->dateTime('work_started_at')->nullable();
            $table->dateTime('work_ended_at')->nullable();

            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('screen_view');
            $table->index(['queue_type', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_queues');
    }
};
