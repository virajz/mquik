<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('job_card_history_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_card_id')->constrained('job_cards')->cascadeOnDelete();
            $table->string('event_type', 40);
            $table->foreignId('actor_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('summary');
            $table->json('payload')->nullable();
            $table->dateTime('occurred_at');
            $table->timestamps();

            $table->index(['job_card_id', 'occurred_at']);
            $table->index(['event_type', 'occurred_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('job_card_history_events');
    }
};
