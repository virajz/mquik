<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Settings a workshop changes, kept out of `.env`.
 *
 * Environment variables need a deploy and shell access to change, which is the
 * wrong home for "how many services to list". Config files stay the source of
 * defaults; a row here overrides one when it exists.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('app_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();   // dot notation, e.g. service_history.services_shown
            $table->text('value')->nullable();
            $table->foreignId('updated_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('app_settings');
    }
};
