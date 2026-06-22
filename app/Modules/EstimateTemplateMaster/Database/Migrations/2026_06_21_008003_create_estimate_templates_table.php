<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('estimate_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('code', 32)->nullable()->unique();
            $table->foreignId('inventory_group_id')->nullable()->constrained('inventory_groups')->nullOnDelete();
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['is_active', 'name']);
        });

        Schema::create('estimate_template_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('estimate_template_id')->constrained('estimate_templates')->cascadeOnDelete();
            $table->string('line_type', 10);  // spare | labour
            $table->foreignId('spare_id')->nullable()->constrained('spares')->nullOnDelete();
            $table->foreignId('labour_id')->nullable()->constrained('labours')->nullOnDelete();
            $table->decimal('default_qty', 10, 2)->default(1);
            $table->unsignedInteger('sequence_no')->default(0);
            $table->timestamps();

            $table->index(['estimate_template_id', 'sequence_no']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('estimate_template_items');
        Schema::dropIfExists('estimate_templates');
    }
};
