<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_estimates', function (Blueprint $table) {
            $table->id();
            $table->string('estimate_no', 32)->nullable()->unique();
            $table->string('estimate_type', 20)->default('before');   // before | after | additional | warranty
            $table->string('parts_category', 20)->default('any');     // genuine | after_market | any
            $table->string('status', 30)->default('pending');         // see SalesEstimate::statuses()
            $table->string('discount_type', 20)->nullable();          // percentage | flat | on_mrp | on_rcp
            $table->decimal('discount_value', 12, 2)->default(0);
            $table->string('labour_price_tier', 30)->default('retail'); // retail | insurance_approved | special_customer | bulk

            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->foreignId('customer_vehicle_id')->constrained('customer_vehicles')->cascadeOnDelete();
            $table->foreignId('job_card_id')->nullable()->constrained('job_cards')->nullOnDelete();
            $table->foreignId('damage_cause_id')->nullable()->constrained('damage_causes')->nullOnDelete();
            $table->foreignId('department_id')->nullable()->constrained('workshop_departments')->nullOnDelete();
            $table->foreignId('service_type_id')->nullable()->constrained('service_types')->nullOnDelete();
            $table->foreignId('advisor_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->foreignId('technician_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->foreignId('insurance_company_id')->nullable()->constrained('insurance_companies')->nullOnDelete();
            $table->foreignId('service_package_id')->nullable()->constrained('service_packages')->nullOnDelete();
            $table->foreignId('estimate_template_id')->nullable()->constrained('estimate_templates')->nullOnDelete();
            $table->foreignId('old_estimate_id')->nullable()->constrained('sales_estimates')->nullOnDelete();
            $table->foreignId('revision_reason_id')->nullable()->constrained('estimate_revision_reasons')->nullOnDelete();

            $table->decimal('parts_total', 14, 2)->default(0);
            $table->decimal('labour_total', 14, 2)->default(0);
            $table->decimal('discount_total', 14, 2)->default(0);
            $table->decimal('tax_total', 14, 2)->default(0);
            $table->decimal('grand_total', 14, 2)->default(0);
            $table->decimal('insurance_pass_percent', 5, 2)->default(0);

            $table->string('policy_no', 60)->nullable();
            $table->text('notes')->nullable();
            $table->dateTime('prepared_at')->nullable();
            $table->dateTime('sent_at')->nullable();
            $table->dateTime('approved_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index(['customer_vehicle_id', 'status']);
            $table->index(['job_card_id', 'status']);
        });

        Schema::create('sales_estimate_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sales_estimate_id')->constrained('sales_estimates')->cascadeOnDelete();
            $table->string('line_type', 10);  // spare | labour
            $table->foreignId('spare_id')->nullable()->constrained('spares')->nullOnDelete();
            $table->foreignId('labour_id')->nullable()->constrained('labours')->nullOnDelete();
            $table->foreignId('inventory_group_id')->nullable()->constrained('inventory_groups')->nullOnDelete();
            $table->foreignId('tax_id')->nullable()->constrained('taxes')->nullOnDelete();
            $table->string('description');
            $table->string('hsn_code', 16)->nullable();
            $table->decimal('qty', 10, 2)->default(1);
            $table->decimal('unit_rate', 14, 2)->default(0);
            $table->decimal('tax_percent', 5, 2)->default(0);
            $table->decimal('line_total', 14, 2)->default(0);
            $table->boolean('is_insurance_approved')->default(false);
            $table->unsignedInteger('sequence_no')->default(0);
            $table->timestamps();

            $table->index(['sales_estimate_id', 'sequence_no']);
            $table->index(['sales_estimate_id', 'line_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_estimate_items');
        Schema::dropIfExists('sales_estimates');
    }
};
