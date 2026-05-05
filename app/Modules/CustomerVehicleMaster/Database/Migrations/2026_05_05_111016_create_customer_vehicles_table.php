<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_vehicles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('customers')->restrictOnDelete();
            $table->foreignId('model_id')->constrained('vehicle_models')->restrictOnDelete();
            $table->foreignId('variant_id')->nullable()->constrained('vehicle_variants')->nullOnDelete();
            $table->foreignId('color_id')->nullable()->constrained('vehicle_colors')->nullOnDelete();
            $table->string('registration_no', 20)->unique(); // GJ 05 AA 1234
            $table->unsignedSmallInteger('year_of_manufacture')->nullable();
            $table->string('vin', 17)->nullable()->unique();   // 17-char ISO standard
            $table->string('engine_no', 30)->nullable();
            $table->unsignedInteger('odometer_km')->nullable();
            $table->date('insurance_expiry')->nullable();
            $table->date('puc_expiry')->nullable();
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['customer_id', 'is_active']);
            $table->index('registration_no');
            $table->index('insurance_expiry');
            $table->index('puc_expiry');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_vehicles');
    }
};
