<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('job_cards', function (Blueprint $table) {
            $table->id();
            $table->string('job_card_no', 32)->nullable();

            $table->foreignId('appointment_id')->nullable()->constrained('appointments')->nullOnDelete();
            $table->foreignId('gate_event_id')->nullable()->constrained('gate_events')->nullOnDelete();
            $table->foreignId('repeat_of_job_card_id')->nullable()->constrained('job_cards')->nullOnDelete();

            $table->foreignId('customer_id')->constrained('customers')->restrictOnDelete();
            $table->foreignId('customer_vehicle_id')->constrained('customer_vehicles')->restrictOnDelete();
            $table->foreignId('workshop_department_id')->constrained('workshop_departments')->restrictOnDelete();
            $table->foreignId('service_type_id')->nullable()->constrained('service_types')->nullOnDelete();
            $table->foreignId('service_package_id')->nullable()->constrained('service_packages')->nullOnDelete();
            $table->foreignId('assigned_advisor_id')->constrained('employees')->restrictOnDelete();
            $table->foreignId('assigned_technician_id')->nullable()->constrained('employees')->nullOnDelete();

            $table->dateTime('opened_at');
            $table->dateTime('promised_at')->nullable();
            $table->dateTime('closed_at')->nullable();

            $table->unsignedInteger('km_at_service')->nullable();
            $table->string('fuel_level', 20)->nullable();  // empty | quarter | half | three_quarter | full
            $table->text('suggested_services')->nullable();

            $table->boolean('terms_accepted')->default(false);
            $table->dateTime('terms_accepted_at')->nullable();
            $table->string('customer_signature_path')->nullable();

            $table->string('status', 30)->default('open');  // open | in_progress | awaiting_parts | awaiting_approval | completed | closed | cancelled
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique('job_card_no');
            $table->index('opened_at');
            $table->index(['status', 'opened_at']);
            $table->index(['customer_vehicle_id', 'opened_at']);
            $table->index(['assigned_advisor_id', 'status']);
            $table->index(['assigned_technician_id', 'status']);
            $table->index(['workshop_department_id', 'status']);
        });

        Schema::create('job_card_complaints', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_card_id')->constrained('job_cards')->cascadeOnDelete();
            $table->foreignId('complaint_type_id')->nullable()->constrained('complaint_types')->nullOnDelete();
            $table->text('description');
            $table->string('severity', 10)->default('medium');  // low | medium | high
            $table->boolean('is_resolved')->default(false);
            $table->text('resolution_notes')->nullable();
            $table->unsignedSmallInteger('sequence_no')->default(1);
            $table->timestamps();

            $table->index(['job_card_id', 'sequence_no']);
            $table->index(['job_card_id', 'is_resolved']);
        });

        Schema::create('job_card_inventory_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_card_id')->constrained('job_cards')->cascadeOnDelete();
            $table->foreignId('vehicle_inventory_item_id')->constrained('vehicle_inventory_items')->restrictOnDelete();
            $table->boolean('is_present')->default(false);
            $table->text('condition_notes')->nullable();
            $table->timestamps();

            $table->unique(['job_card_id', 'vehicle_inventory_item_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('job_card_inventory_items');
        Schema::dropIfExists('job_card_complaints');
        Schema::dropIfExists('job_cards');
    }
};
