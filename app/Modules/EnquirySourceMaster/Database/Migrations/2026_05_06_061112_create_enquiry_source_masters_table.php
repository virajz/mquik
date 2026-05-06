<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('enquiry_sources', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('code', 20)->nullable()->unique();
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();

            // Composite index for the most common query: active sources, sorted by name
            $table->index(['is_active', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('enquiry_sources');
    }
};
