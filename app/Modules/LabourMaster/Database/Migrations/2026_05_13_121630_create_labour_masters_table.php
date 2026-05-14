<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('labours', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('labour_code', 64)->nullable();
            $table->text('description')->nullable();
            $table->string('hsn_sac_code', 16)->nullable();

            $table->foreignId('vehicle_segment_id')->nullable()->constrained('vehicle_segments')->nullOnDelete();
            $table->foreignId('tax_id')->nullable()->constrained('taxes')->nullOnDelete();
            $table->foreignId('workshop_department_id')->nullable()->constrained('workshop_departments')->nullOnDelete();
            $table->foreignId('inventory_group_id')->nullable()->constrained('inventory_groups')->nullOnDelete();
            $table->foreignId('inventory_sub_group_id')->nullable()->constrained('inventory_groups')->nullOnDelete();

            $table->decimal('rate_before_tax', 12, 2)->default(0);
            $table->boolean('is_osl')->default(false);

            $table->text('remark')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique('labour_code');
            $table->index('name');
            $table->index('hsn_sac_code');
            $table->index(['is_active', 'name']);
            $table->index(['is_osl', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('labours');
    }
};
