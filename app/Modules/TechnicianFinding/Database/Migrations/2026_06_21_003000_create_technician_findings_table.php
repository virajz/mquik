<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('technician_findings', function (Blueprint $table) {
            $table->id();
            $table->string('finding_no', 32)->nullable()->unique();
            $table->foreignId('job_card_id')->constrained('job_cards')->cascadeOnDelete();
            $table->foreignId('vehicle_inspection_order_id')->nullable()->constrained('vehicle_inspection_orders')->nullOnDelete();
            $table->string('finding_type', 20)->default('spare');  // spare | labour
            $table->foreignId('spare_id')->nullable()->constrained('spares')->nullOnDelete();
            $table->foreignId('labour_id')->nullable()->constrained('labours')->nullOnDelete();
            $table->foreignId('reported_by_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->string('description');
            $table->decimal('quantity', 10, 2)->nullable();
            $table->decimal('estimated_amount', 12, 2)->nullable();
            $table->string('recommendation', 30)->default('new_issue');  // new_issue | additional_parts | additional_labour
            $table->string('status', 20)->default('recommended');        // recommended | approved | rejected | converted
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['job_card_id', 'status']);
            $table->index(['status', 'created_at']);
            $table->index(['vehicle_inspection_order_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('technician_findings');
    }
};
