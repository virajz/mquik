<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pickup_drops', function (Blueprint $table) {
            $table->id();
            $table->string('pickup_drop_no', 32)->nullable();
            $table->string('direction', 10);  // pickup | drop
            $table->foreignId('appointment_id')->nullable()->constrained('appointments')->nullOnDelete();
            $table->foreignId('customer_id')->constrained('customers')->restrictOnDelete();
            $table->foreignId('customer_vehicle_id')->constrained('customer_vehicles')->restrictOnDelete();

            $table->dateTime('scheduled_at');
            $table->text('address');
            $table->string('contact_phone', 20)->nullable();

            $table->foreignId('driver_employee_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->foreignId('vendor_courier_id')->nullable()->constrained('courier_companies')->nullOnDelete();

            $table->string('status', 20)->default('scheduled'); // scheduled|picked|in_transit|delivered|cancelled
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique('pickup_drop_no');
            $table->index('scheduled_at');
            $table->index(['status', 'scheduled_at']);
            $table->index(['direction', 'status']);
            $table->index('appointment_id');
            $table->index(['driver_employee_id', 'scheduled_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pickup_drops');
    }
};
