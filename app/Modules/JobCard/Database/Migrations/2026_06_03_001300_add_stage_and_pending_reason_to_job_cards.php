<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('job_cards', function (Blueprint $table) {
            $table->foreignId('current_stage_id')->nullable()->after('status')
                ->constrained('job_stages')->nullOnDelete();
            $table->foreignId('pending_reason_id')->nullable()->after('current_stage_id')
                ->constrained('job_card_pending_reasons')->nullOnDelete();
            $table->dateTime('expected_completion_at')->nullable()->after('promised_at');

            $table->index('current_stage_id');
        });
    }

    public function down(): void
    {
        Schema::table('job_cards', function (Blueprint $table) {
            $table->dropForeign(['current_stage_id']);
            $table->dropForeign(['pending_reason_id']);
            $table->dropColumn(['current_stage_id', 'pending_reason_id', 'expected_completion_at']);
        });
    }
};
