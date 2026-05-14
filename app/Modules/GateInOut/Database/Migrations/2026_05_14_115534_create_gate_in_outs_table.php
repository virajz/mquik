<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gate_events', function (Blueprint $table) {
            $table->id();
            $table->string('gate_event_no', 32)->nullable();
            $table->string('direction', 10);  // in | out
            $table->dateTime('gated_at');
            $table->string('registration_no', 20);
            $table->foreignId('customer_vehicle_id')->nullable()->constrained('customer_vehicles')->nullOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->string('source', 10)->default('manual');  // anpr | manual
            $table->string('captured_image_path')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('recorded_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique('gate_event_no');
            $table->index('gated_at');
            $table->index(['direction', 'gated_at']);
            $table->index('registration_no');
            $table->index(['customer_vehicle_id', 'gated_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gate_events');
    }
};
