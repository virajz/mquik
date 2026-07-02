<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('final_inspections', function (Blueprint $table) {
            $table->id();
            $table->string('inspection_no', 32)->nullable()->unique();
            $table->foreignId('job_card_id')->constrained('job_cards')->cascadeOnDelete();
            $table->foreignId('digital_inspection_id')->nullable()->constrained('digital_inspections')->nullOnDelete();
            $table->foreignId('inspection_template_id')->nullable()->constrained('inspection_templates')->nullOnDelete();
            $table->foreignId('inspector_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->string('work_completion_type', 20)->nullable();  // fully | partially | deferred
            $table->string('status', 20)->default('pending');        // pending | in_progress | on_hold | completed | rework | cancelled | not_applicable
            $table->foreignId('rework_reason_id')->nullable()->constrained('rework_reasons')->nullOnDelete();
            $table->string('additional_work_recommendation', 30)->nullable(); // new_issue | additional_parts | additional_labour
            $table->dateTime('started_at')->nullable();
            $table->dateTime('ended_at')->nullable();
            $table->text('summary_notes')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index(['job_card_id', 'status']);
        });

        Schema::create('final_inspection_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('final_inspection_id')->constrained('final_inspections')->cascadeOnDelete();
            $table->foreignId('inspection_item_id')->nullable()->constrained('inspection_items')->nullOnDelete();
            $table->foreignId('inspection_item_group_id')->nullable()->constrained('inspection_item_groups')->nullOnDelete();
            $table->string('label');
            $table->string('result', 15)->default('pending');
            $table->string('recommendation', 20)->nullable();
            $table->string('severity', 20)->nullable();
            $table->text('observation')->nullable();
            $table->string('before_photo_path')->nullable();
            $table->string('after_photo_path')->nullable();
            $table->string('damage_photo_path')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedInteger('sequence_no')->default(0);
            $table->timestamps();

            $table->index(['final_inspection_id', 'sequence_no']);
        });

        Schema::create('final_inspection_pauses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('final_inspection_id')->constrained('final_inspections')->cascadeOnDelete();
            $table->dateTime('paused_at')->nullable();
            $table->dateTime('resumed_at')->nullable();
            $table->string('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('final_inspection_pauses');
        Schema::dropIfExists('final_inspection_items');
        Schema::dropIfExists('final_inspections');
    }
};
