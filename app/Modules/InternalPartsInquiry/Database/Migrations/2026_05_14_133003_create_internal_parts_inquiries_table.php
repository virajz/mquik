<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('internal_parts_inquiries', function (Blueprint $table) {
            $table->id();
            $table->string('ipi_no', 32)->nullable();
            $table->foreignId('job_card_id')->constrained('job_cards')->cascadeOnDelete();
            $table->foreignId('requested_by_employee_id')->constrained('employees')->restrictOnDelete();
            $table->foreignId('target_employee_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->dateTime('requested_at');
            $table->dateTime('needed_by')->nullable();
            $table->string('status', 20)->default('open');  // open | responded | closed | cancelled
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique('ipi_no');
            $table->index(['status', 'requested_at']);
            $table->index(['job_card_id', 'status']);
            $table->index(['target_employee_id', 'status']);
        });

        Schema::create('internal_parts_inquiry_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('internal_parts_inquiry_id')->constrained('internal_parts_inquiries')->cascadeOnDelete();
            $table->foreignId('spare_id')->nullable()->constrained('spares')->nullOnDelete();
            $table->string('description');  // free-text fallback when spare_id not picked
            $table->decimal('quantity', 12, 2);
            $table->dateTime('needed_by_date')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedSmallInteger('sequence_no')->default(1);
            $table->timestamps();

            $table->index(['internal_parts_inquiry_id', 'sequence_no']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('internal_parts_inquiry_items');
        Schema::dropIfExists('internal_parts_inquiries');
    }
};
