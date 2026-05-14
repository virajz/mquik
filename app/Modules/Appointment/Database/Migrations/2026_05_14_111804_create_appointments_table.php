<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('appointments', function (Blueprint $table) {
            $table->id();
            $table->string('appointment_no', 32)->nullable();
            $table->dateTime('appointment_at');
            $table->string('channel', 20);  // app | website | email | phone_call

            $table->foreignId('customer_id')->constrained('customers')->restrictOnDelete();
            $table->foreignId('customer_vehicle_id')->constrained('customer_vehicles')->restrictOnDelete();
            $table->foreignId('service_type_id')->nullable()->constrained('service_types')->nullOnDelete();
            $table->foreignId('workshop_department_id')->constrained('workshop_departments')->restrictOnDelete();
            $table->foreignId('assigned_advisor_id')->constrained('employees')->restrictOnDelete();
            $table->foreignId('assigned_technician_id')->nullable()->constrained('employees')->nullOnDelete();

            $table->boolean('requires_pickup')->default(false);
            $table->text('pickup_address')->nullable();
            $table->string('pickup_contact_phone', 20)->nullable();

            $table->string('status', 20)->default('pending'); // pending|confirmed|completed|cancelled|no_show
            $table->text('notes')->nullable();

            $table->timestamps();

            $table->unique('appointment_no');
            $table->index('appointment_at');
            $table->index(['status', 'appointment_at']);
            $table->index(['assigned_advisor_id', 'appointment_at']);
            $table->index(['assigned_technician_id', 'appointment_at']);
            $table->index(['customer_id', 'appointment_at']);
            $table->index(['workshop_department_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appointments');
    }
};
