<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('digital_inspections', function (Blueprint $table) {
            $table->id();
            $table->string('inspection_no', 32)->nullable();
            $table->foreignId('job_card_id')->constrained('job_cards')->cascadeOnDelete();
            $table->foreignId('inspection_template_id')->constrained('inspection_templates')->restrictOnDelete();
            $table->foreignId('assigned_technician_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->foreignId('floor_incharge_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->dateTime('started_at')->nullable();
            $table->dateTime('completed_at')->nullable();
            $table->string('status', 20)->default('pending');  // pending | wip | completed | cancelled
            $table->text('summary_notes')->nullable();
            $table->timestamps();

            $table->unique('inspection_no');
            $table->index(['status', 'created_at']);
            $table->index(['job_card_id', 'status']);
            $table->index(['assigned_technician_id', 'status']);
        });

        Schema::create('digital_inspection_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('digital_inspection_id')->constrained('digital_inspections')->cascadeOnDelete();
            $table->foreignId('inspection_item_id')->constrained('inspection_items')->restrictOnDelete();
            $table->foreignId('inspection_item_group_id')->nullable()->constrained('inspection_item_groups')->nullOnDelete();
            $table->string('outcome', 10)->default('pending');  // pending | rep | adj | ok | ia | fa
            $table->text('notes')->nullable();
            $table->string('image_path')->nullable();
            $table->unsignedInteger('sequence_no')->default(0);
            $table->timestamps();

            $table->index(['digital_inspection_id', 'sequence_no']);
            $table->index(['digital_inspection_id', 'outcome']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('digital_inspection_items');
        Schema::dropIfExists('digital_inspections');
    }
};
