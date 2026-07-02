<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('performance_slabs', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('code', 20)->nullable()->unique();
            $table->decimal('min_percent', 5, 2)->default(0);   // inclusive lower bound of achievement %
            $table->decimal('max_percent', 5, 2)->nullable();   // null = open-ended top slab
            $table->decimal('incentive_amount', 12, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['is_active', 'min_percent']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('performance_slabs');
    }
};
