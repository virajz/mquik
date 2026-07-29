<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('final_work_orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_no', 32)->nullable()->unique();
            $table->foreignId('job_card_id')->constrained('job_cards')->cascadeOnDelete();
            $table->foreignId('department_id')->nullable()->constrained('workshop_departments')->nullOnDelete();
            $table->foreignId('service_type_id')->nullable()->constrained('service_types')->nullOnDelete();
            $table->foreignId('advisor_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->foreignId('technician_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->foreignId('bay_id')->nullable()->constrained('bays')->nullOnDelete();
            $table->foreignId('inspection_template_id')->nullable()->constrained('inspection_templates')->nullOnDelete();
            $table->string('work_priority', 20)->default('normal');       // normal | high | urgent
            $table->string('status', 30)->default('assignment_pending');  // assignment_pending | assigned | wip | on_hold | completed | cancelled
            $table->string('completion_type', 20)->nullable();            // fully | partially | deferred
            $table->foreignId('hold_reason_id')->nullable()->constrained('work_order_hold_reasons')->nullOnDelete();
            $table->foreignId('rework_reason_id')->nullable()->constrained('rework_reasons')->nullOnDelete();
            $table->foreignId('delay_reason_id')->nullable()->constrained('delay_reasons')->nullOnDelete();
            $table->dateTime('assigned_at')->nullable();
            $table->dateTime('accepted_at')->nullable();
            $table->dateTime('started_at')->nullable();
            $table->dateTime('ended_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index(['job_card_id', 'status']);
            $table->index(['technician_id', 'status']);
            $table->index(['bay_id', 'status']);
        });

        Schema::create('final_work_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('final_work_order_id')->constrained('final_work_orders')->cascadeOnDelete();
            $table->foreignId('inspection_item_id')->nullable()->constrained('inspection_items')->nullOnDelete();
            $table->foreignId('inspection_item_group_id')->nullable()->constrained('inspection_item_groups')->nullOnDelete();
            $table->string('label');
            $table->string('result', 10)->default('pending');  // pending | ok | ia | fa
            $table->string('before_photo_path')->nullable();
            $table->string('after_photo_path')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedInteger('sequence_no')->default(0);
            $table->timestamps();

            $table->index(['final_work_order_id', 'sequence_no']);
            $table->index(['final_work_order_id', 'result']);
        });

        Schema::create('final_work_order_pauses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('final_work_order_id')->constrained('final_work_orders')->cascadeOnDelete();
            $table->foreignId('hold_reason_id')->nullable()->constrained('work_order_hold_reasons')->nullOnDelete();
            $table->dateTime('paused_at')->nullable();
            $table->dateTime('resumed_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['final_work_order_id', 'paused_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('final_work_order_pauses');
        Schema::dropIfExists('final_work_order_items');
        Schema::dropIfExists('final_work_orders');
    }
};
