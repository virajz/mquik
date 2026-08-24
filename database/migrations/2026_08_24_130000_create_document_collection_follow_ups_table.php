<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Chasing documents is a series of calls, not one field: each attempt records
 * when, by whom, over what channel, and what the customer said.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_collection_follow_ups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_collection_id')->constrained()->cascadeOnDelete();
            $table->timestamp('followed_up_at');
            $table->foreignId('followed_up_by_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->foreignId('follow_up_mode_id')->nullable()->constrained('follow_up_modes')->nullOnDelete();
            $table->string('customer_response', 500)->nullable();
            $table->timestamps();

            $table->index(['document_collection_id', 'followed_up_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_collection_follow_ups');
    }
};
