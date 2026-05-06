<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_groups', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('code', 20)->nullable()->unique();
            $table->foreignId('parent_id')
                ->nullable()
                ->constrained('inventory_groups')
                ->nullOnDelete();
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();

            // Composite index for the most common query: active groups, sorted by name
            $table->index(['is_active', 'name']);
            $table->index('parent_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_groups');
    }
};
