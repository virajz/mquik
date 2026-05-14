<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('job_cards', function (Blueprint $table) {
            $table->foreignId('cancel_reason_id')->nullable()->after('status')->constrained('job_card_cancel_reasons')->nullOnDelete();
            $table->dateTime('cancelled_at')->nullable()->after('cancel_reason_id');
            $table->text('cancellation_notes')->nullable()->after('cancelled_at');
        });
    }

    public function down(): void
    {
        Schema::table('job_cards', function (Blueprint $table) {
            $table->dropForeign(['cancel_reason_id']);
            $table->dropColumn(['cancel_reason_id', 'cancelled_at', 'cancellation_notes']);
        });
    }
};
