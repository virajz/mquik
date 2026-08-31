<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The recommendation wording a technician picks on a checklist row.
 *
 * Filed under a Category and, optionally, a Sub Category, so the picker on an
 * inspection item shows a short relevant list instead of every phrase in the
 * workshop. Several apply to one checkpoint, hence the pivot.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recommendation_descriptions', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code', 20)->nullable()->unique();
            $table->foreignId('category_id')->constrained('recommendation_categories')->cascadeOnDelete();
            $table->foreignId('sub_category_id')->nullable()->constrained('recommendation_categories')->nullOnDelete();
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sequence_no')->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('name');
            $table->index(['category_id', 'sub_category_id', 'is_active']);
            $table->unique(['category_id', 'sub_category_id', 'name'], 'rec_desc_unique_within_category');
        });

        // Several recommendations can apply to one checkpoint.
        Schema::create('digital_inspection_item_recommendations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('digital_inspection_item_id')
                ->constrained('digital_inspection_items', indexName: 'di_item_rec_item_fk')
                ->cascadeOnDelete();
            $table->foreignId('recommendation_description_id')
                ->constrained('recommendation_descriptions', indexName: 'di_item_rec_desc_fk')
                ->cascadeOnDelete();
            $table->unsignedSmallInteger('sequence_no')->default(0);
            $table->timestamps();

            $table->unique(
                ['digital_inspection_item_id', 'recommendation_description_id'],
                'di_item_rec_unique',
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('digital_inspection_item_recommendations');
        Schema::dropIfExists('recommendation_descriptions');
    }
};
