<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_packages', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code', 32)->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_amc')->default(false);
            $table->unsignedSmallInteger('validity_months')->nullable();
            $table->unsignedInteger('validity_km')->nullable();
            $table->decimal('total_price', 12, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique('code');
            $table->index('name');
            $table->index(['is_active', 'name']);
            $table->index(['is_amc', 'is_active']);
        });

        Schema::create('service_package_services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_package_id')->constrained('service_packages')->cascadeOnDelete();
            $table->foreignId('service_type_id')->constrained('service_types')->restrictOnDelete();
            $table->unsignedSmallInteger('sequence_no')->default(1);
            $table->unsignedSmallInteger('due_after_months')->nullable();
            $table->unsignedInteger('due_after_km')->nullable();
            $table->string('notes', 255)->nullable();
            $table->timestamps();

            $table->index(['service_package_id', 'sequence_no']);
            $table->index('service_type_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_package_services');
        Schema::dropIfExists('service_packages');
    }
};
