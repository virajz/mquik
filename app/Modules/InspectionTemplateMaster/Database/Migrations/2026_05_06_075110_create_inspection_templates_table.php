<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inspection_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('code', 30)->nullable()->unique();
            $table->string('applies_to', 20); // pms | tyre | bodyshop | basic | custom
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['applies_to', 'name']);
            $table->index(['is_active', 'name']);
        });

        Schema::create('inspection_template_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inspection_template_id')->constrained('inspection_templates')->cascadeOnDelete();
            $table->foreignId('inspection_item_id')->constrained('inspection_items')->cascadeOnDelete();
            $table->unsignedInteger('position')->default(0);
            $table->boolean('is_required')->default(true);
            $table->timestamps();

            $table->unique(['inspection_template_id', 'inspection_item_id']);
            $table->index(['inspection_template_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inspection_template_items');
        Schema::dropIfExists('inspection_templates');
    }
};
