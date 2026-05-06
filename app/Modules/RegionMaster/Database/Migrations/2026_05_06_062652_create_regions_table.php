<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('regions', function (Blueprint $table) {
            $table->id();
            // String not enum — Postgres/SQLite portability + matches CustomerMaster's customer_type precedent.
            $table->string('kind', 20); // state | city | area | pincode
            $table->string('name');
            $table->string('code', 20)->nullable();
            $table->foreignId('parent_id')
                ->nullable()
                ->constrained('regions')
                ->nullOnDelete();
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['kind', 'name']);
            $table->index(['parent_id', 'name']);
            $table->index(['is_active', 'name']);

            // Names unique within their parent + kind combination.
            $table->unique(['kind', 'parent_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('regions');
    }
};
