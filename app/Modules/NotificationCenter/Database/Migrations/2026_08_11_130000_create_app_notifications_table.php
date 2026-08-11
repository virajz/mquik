<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * In-app alerts addressed to an employee.
 *
 * Deliberately not Laravel's `notifications` table: that one is keyed to a
 * notifiable *user*, and in this application the people who need alerting —
 * service advisor, store in-charge, floor technician — are `employees` with no
 * user account. Addressing employees directly is the only thing that actually
 * reaches them. `user_id` is kept alongside for the day the two are linked.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('app_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->nullable()->constrained('employees')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->cascadeOnDelete();

            $table->string('type', 50);                 // parts_available, goods_received, …
            $table->string('title');
            $table->text('body')->nullable();
            $table->string('url')->nullable();          // where clicking it should land

            // What the alert is about, for de-duping and deep links.
            $table->string('subject_type', 120)->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();

            $table->foreignId('actor_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('read_at')->nullable();
            $table->timestamps();

            $table->index(['employee_id', 'read_at']);
            $table->index(['type', 'created_at']);
            $table->index(['subject_type', 'subject_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('app_notifications');
    }
};
