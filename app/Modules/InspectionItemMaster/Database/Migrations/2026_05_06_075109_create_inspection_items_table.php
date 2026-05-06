<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inspection_items', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code', 30)->nullable();
            $table->foreignId('inspection_item_group_id')->nullable()->constrained('inspection_item_groups')->nullOnDelete();
            $table->string('check_type', 20); // visual | measurement | yes_no | rating
            $table->string('measurement_unit', 20)->nullable(); // optional, e.g. mm, %, bar — only meaningful when check_type=measurement
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['inspection_item_group_id', 'name']);
            $table->index(['inspection_item_group_id', 'name']);
            $table->index(['check_type', 'name']);
            $table->index(['is_active', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inspection_items');
    }
};
