<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('checklist_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code', 30)->nullable()->unique();
            $table->foreignId('checklist_group_id')->nullable()->constrained('checklist_groups')->nullOnDelete();
            $table->string('applies_to', 20); // job_card | pickup | delivery | claim | generic
            $table->json('items'); // array of {label, is_required} objects
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['checklist_group_id', 'name']);
            $table->index(['applies_to', 'name']);
            $table->index(['is_active', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('checklist_templates');
    }
};
