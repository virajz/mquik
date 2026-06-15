<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('job_card_requested_repair', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_card_id')->constrained('job_cards')->cascadeOnDelete();
            $table->foreignId('requested_repair_id')->constrained('requested_repairs')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['job_card_id', 'requested_repair_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('job_card_requested_repair');
    }
};
