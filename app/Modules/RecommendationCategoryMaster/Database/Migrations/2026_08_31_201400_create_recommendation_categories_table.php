<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Category and Sub Category for recommendation descriptions.
 *
 * One self-referencing table rather than two, the way `inventory_groups`
 * already does it here: a row with no parent is a Category, a row with one is
 * a Sub Category of it. Two tables would duplicate every field and every screen
 * to express the same thing.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recommendation_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code', 20)->nullable()->unique();
            $table->foreignId('parent_id')->nullable()->constrained('recommendation_categories')->cascadeOnDelete();
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sequence_no')->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('name');
            $table->index(['parent_id', 'is_active']);
            // A sub category's name only has to be unique inside its parent.
            $table->unique(['parent_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recommendation_categories');
    }
};
