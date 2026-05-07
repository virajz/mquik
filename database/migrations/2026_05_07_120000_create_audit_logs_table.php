<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('user_name')->nullable(); // snapshot in case user is deleted
            $table->string('event', 20); // created | updated | deleted | restored
            $table->string('model_type'); // FQCN, e.g. App\Modules\CustomerMaster\Models\CustomerMaster
            $table->unsignedBigInteger('model_id');
            $table->string('model_label')->nullable(); // human label, e.g. "RAVI SHARMA" or "EMP-00001"
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->ipAddress('ip_address')->nullable();
            $table->string('user_agent', 1024)->nullable();
            $table->timestamp('created_at')->useCurrent();
            // No updated_at — audit rows are immutable.

            $table->index(['model_type', 'model_id']);
            $table->index(['user_id', 'created_at']);
            $table->index('created_at');
            $table->index('event');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
