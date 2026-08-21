<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * One-time codes sent to a user's phone.
 *
 * The code is stored hashed — a leaked table should not hand over live codes.
 * Attempts are counted so a code cannot be brute-forced, and each row is single
 * use via `consumed_at`.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_otps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('purpose', 30);        // password_reset / phone_verification
            $table->string('code_hash');
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->dateTime('expires_at');
            $table->dateTime('consumed_at')->nullable();
            $table->string('sent_to', 20)->nullable();   // the number it went to, for the audit trail
            $table->timestamps();

            $table->index(['user_id', 'purpose', 'consumed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_otps');
    }
};
